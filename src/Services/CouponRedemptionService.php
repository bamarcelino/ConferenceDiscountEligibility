<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Services;

use App\Models\Payment;
use ConferenceDiscountEligibility\Data\CouponApplicationResult;
use ConferenceDiscountEligibility\Enums\CouponStatus;
use ConferenceDiscountEligibility\Enums\EligibilityType;
use ConferenceDiscountEligibility\Models\ConferenceDiscountCoupon;
use ConferenceDiscountEligibility\Models\ConferenceDiscountCouponRedemption;
use ConferenceDiscountEligibility\Models\ConferenceDiscountPaymentSnapshot;
use ConferenceDiscountEligibility\Support\FullDiscountPolicy;
use ConferenceDiscountEligibility\Support\Money;
use ConferenceDiscountEligibility\Support\PaymentSafety;
use Illuminate\Support\Facades\DB;

final class CouponRedemptionService
{
    public function __construct(
        private readonly CouponEligibilityService $eligibility,
        private readonly PaymentDiscountService $discounts,
        private readonly SnapshotService $snapshots,
        private readonly AuditLogger $auditLogger,
        private readonly FullDiscountSettlementService $fullDiscountSettlement,
    ) {}

    public function apply(Payment $payment, string $code): CouponApplicationResult
    {
        return DB::transaction(function () use ($payment, $code): CouponApplicationResult {
            $locked = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());
            if (! PaymentSafety::canRecalculate($locked)) {
                return new CouponApplicationResult(
                    $locked,
                    'locked',
                    'coupon_payment_locked',
                    false,
                    false,
                );
            }

            $evaluation = $this->eligibility->evaluateCode($locked, $code, now(), true);
            $coupon = $evaluation['coupon'];
            $candidate = $evaluation['candidate'];

            if ($coupon === null || ! $candidate->eligible) {
                $this->auditLogger->log(
                    action: 'coupon_rejected',
                    scheduledConferenceId: (int) $locked->scheduled_conference_id,
                    auditable: $locked,
                    affectedUserId: $locked->user_id,
                    context: [
                        'rejection_reason' => $candidate->rejectionReason,
                        'candidate' => $candidate->toArray(),
                    ],
                    origin: 'payment_coupon_form',
                );

                return new CouponApplicationResult(
                    $locked,
                    'rejected',
                    'coupon_invalid_or_unavailable',
                    false,
                    false,
                );
            }

            $existing = ConferenceDiscountCouponRedemption::query()
                ->where('payment_id', $locked->getKey())
                ->lockForUpdate()
                ->first();
            $candidates = [$candidate];
            if (
                $existing !== null
                && $existing->statusValue()->isActiveClaim()
                && (int) $existing->coupon_campaign_id !== (int) $coupon->getKey()
            ) {
                $existingCoupon = ConferenceDiscountCoupon::query()
                    ->lockForUpdate()
                    ->find($existing->coupon_campaign_id);
                if ($existingCoupon !== null) {
                    $candidates[] = $this->eligibility->candidateForCoupon(
                        $locked,
                        $existingCoupon,
                        now(),
                        true,
                    );
                }
            }

            [$baseMinor, $originalTotalMinor, $cleanItems, $currency] = $this->paymentAmounts($locked);
            $prepared = $this->discounts->prepareWithCandidates(
                (int) $locked->scheduled_conference_id,
                $locked->user,
                $candidates,
                $baseMinor,
                $originalTotalMinor,
                $cleanItems,
                $currency,
                true,
            );

            $winner = $prepared->selection->winner;
            if ($winner?->type !== EligibilityType::Coupon || $winner->id !== (int) $coupon->getKey()) {
                $this->auditLogger->log(
                    action: 'coupon_valid_but_not_selected',
                    scheduledConferenceId: (int) $locked->scheduled_conference_id,
                    auditable: $locked,
                    affectedUserId: $locked->user_id,
                    context: [
                        'coupon_id' => (int) $coupon->getKey(),
                        'coupon_hint' => (string) $coupon->code_hint,
                        'coupon_percentage_basis_points' => (int) $coupon->percentage_basis_points,
                        'winning_rule' => $winner?->toArray(),
                        'evaluated_rules' => $prepared->selection->evaluatedAsArray(),
                    ],
                    origin: 'payment_coupon_form',
                );

                return new CouponApplicationResult(
                    $locked,
                    'not_selected',
                    'coupon_valid_existing_discount_higher',
                    false,
                    false,
                );
            }

            $oldSnapshot = ConferenceDiscountPaymentSnapshot::query()
                ->where('payment_id', $locked->getKey())
                ->lockForUpdate()
                ->first();
            $alreadyApplied = $existing !== null
                && (int) $existing->coupon_campaign_id === (int) $coupon->getKey()
                && $existing->statusValue()->isActiveClaim()
                && (int) ($oldSnapshot?->coupon_campaign_id ?? 0) === (int) $coupon->getKey();

            if ($existing !== null && (int) $existing->coupon_campaign_id !== (int) $coupon->getKey()) {
                $existing->forceFill([
                    'status' => CouponStatus::Released->value,
                    'released_at' => now(),
                    'metadata' => [
                        ...(is_array($existing->metadata) ? $existing->metadata : []),
                        'release_reason' => 'replaced_by_another_coupon',
                    ],
                ])->save();
            }

            $locked->forceFill([
                'amount' => Money::decimalFloat($prepared->calculation->finalTotalMinor, $currency),
            ])->save();
            $locked->setMeta('additional_items', $prepared->additionalItems);
            $snapshot = $this->snapshots->record($locked, $prepared, 'coupon_redemption');

            $redemption = ConferenceDiscountCouponRedemption::query()->updateOrCreate(
                ['payment_id' => $locked->getKey()],
                [
                    'scheduled_conference_id' => $locked->scheduled_conference_id,
                    'coupon_campaign_id' => $coupon->getKey(),
                    'user_id' => $locked->user_id,
                    'status' => CouponStatus::Reserved->value,
                    'reserved_at' => $existing !== null && (int) $existing->coupon_campaign_id === (int) $coupon->getKey()
                        ? ($existing->reserved_at ?? now())
                        : now(),
                    'consumed_at' => null,
                    'released_at' => null,
                    'metadata' => [
                        'code_hint' => (string) $coupon->code_hint,
                        'campaign_name' => (string) $coupon->name,
                        'percentage_basis_points' => (int) $coupon->percentage_basis_points,
                        'payment_type' => (int) $locked->type,
                        'payment_fee_id' => (int) $locked->payment_fee_id,
                    ],
                ],
            );

            $this->auditLogger->log(
                action: $alreadyApplied ? 'coupon_reapplied' : 'coupon_applied',
                scheduledConferenceId: (int) $locked->scheduled_conference_id,
                auditable: $locked,
                affectedUserId: $locked->user_id,
                oldValues: $oldSnapshot?->toArray(),
                newValues: $snapshot->toArray(),
                context: [
                    'coupon_id' => (int) $coupon->getKey(),
                    'coupon_hint' => (string) $coupon->code_hint,
                    'redemption_id' => (int) $redemption->getKey(),
                    'evaluated_rules' => $prepared->selection->evaluatedAsArray(),
                ],
                origin: 'payment_coupon_form',
            );

            $settled = $this->fullDiscountSettlement->settleIfZero(
                $locked,
                $prepared->calculation->finalTotalMinor,
                $currency,
                'coupon_redemption',
                $locked->user_id ? (int) $locked->user_id : null,
            );
            $completedByFullDiscount = $settled->isPaid()
                && (string) $settled->payment_method === FullDiscountPolicy::PAYMENT_METHOD;

            return new CouponApplicationResult(
                $settled,
                $completedByFullDiscount ? 'completed' : ($alreadyApplied ? 'already_applied' : 'applied'),
                $completedByFullDiscount
                    ? 'coupon_applied_payment_completed'
                    : ($alreadyApplied ? 'coupon_already_applied' : 'coupon_applied_successfully'),
                ! $alreadyApplied,
                true,
            );
        });
    }

    public function remove(Payment $payment): CouponApplicationResult
    {
        return DB::transaction(function () use ($payment): CouponApplicationResult {
            $locked = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());
            if (! PaymentSafety::canRecalculate($locked)) {
                return new CouponApplicationResult($locked, 'locked', 'coupon_payment_locked', false, false);
            }

            $redemption = ConferenceDiscountCouponRedemption::query()
                ->where('payment_id', $locked->getKey())
                ->where('status', CouponStatus::Reserved->value)
                ->lockForUpdate()
                ->first();
            if ($redemption === null) {
                return new CouponApplicationResult($locked, 'none', 'coupon_none_applied', false, false);
            }

            [$baseMinor, $originalTotalMinor, $cleanItems, $currency] = $this->paymentAmounts($locked);
            $prepared = $this->discounts->prepare(
                (int) $locked->scheduled_conference_id,
                $locked->user,
                $baseMinor,
                $originalTotalMinor,
                $cleanItems,
                $currency,
                true,
            );
            $oldSnapshot = ConferenceDiscountPaymentSnapshot::query()
                ->where('payment_id', $locked->getKey())
                ->lockForUpdate()
                ->first();

            $locked->forceFill([
                'amount' => Money::decimalFloat($prepared->calculation->finalTotalMinor, $currency),
            ])->save();
            $locked->setMeta('additional_items', $prepared->additionalItems);
            $snapshot = $this->snapshots->record($locked, $prepared, 'coupon_removed');

            $redemption->forceFill([
                'status' => CouponStatus::Released->value,
                'released_at' => now(),
                'metadata' => [
                    ...(is_array($redemption->metadata) ? $redemption->metadata : []),
                    'release_reason' => 'removed_by_user',
                ],
            ])->save();

            $this->auditLogger->log(
                action: 'coupon_removed',
                scheduledConferenceId: (int) $locked->scheduled_conference_id,
                auditable: $locked,
                affectedUserId: $locked->user_id,
                oldValues: $oldSnapshot?->toArray(),
                newValues: $snapshot->toArray(),
                context: [
                    'coupon_id' => (int) $redemption->coupon_campaign_id,
                    'redemption_id' => (int) $redemption->getKey(),
                ],
                origin: 'payment_coupon_form',
            );

            $settled = $this->fullDiscountSettlement->settleIfZero(
                $locked,
                $prepared->calculation->finalTotalMinor,
                $currency,
                'coupon_removed',
                $locked->user_id ? (int) $locked->user_id : null,
            );

            return new CouponApplicationResult(
                $settled,
                'removed',
                $settled->isPaid()
                    ? 'coupon_removed_automatic_full_discount_completed'
                    : 'coupon_removed_successfully',
                true,
                false,
            );
        });
    }

    public function consumeForPayment(Payment $payment, string $origin = 'payment_fulfilled'): void
    {
        DB::transaction(function () use ($payment, $origin): void {
            $redemption = ConferenceDiscountCouponRedemption::query()
                ->where('payment_id', $payment->getKey())
                ->where('status', CouponStatus::Reserved->value)
                ->lockForUpdate()
                ->first();
            if ($redemption === null) {
                return;
            }

            $redemption->forceFill([
                'status' => CouponStatus::Consumed->value,
                'consumed_at' => now(),
            ])->save();

            $this->auditLogger->log(
                action: 'coupon_consumed',
                scheduledConferenceId: (int) $payment->scheduled_conference_id,
                auditable: $payment,
                affectedUserId: $payment->user_id,
                newValues: $redemption->toArray(),
                context: [
                    'coupon_id' => (int) $redemption->coupon_campaign_id,
                    'redemption_id' => (int) $redemption->getKey(),
                    'payment_method' => $payment->payment_method,
                ],
                origin: $origin,
                actorUserId: null,
            );
        });
    }

    public function reservationForPayment(Payment $payment): ?ConferenceDiscountCouponRedemption
    {
        return ConferenceDiscountCouponRedemption::query()
            ->where('payment_id', $payment->getKey())
            ->whereIn('status', [CouponStatus::Reserved->value, CouponStatus::Consumed->value])
            ->with('coupon')
            ->first();
    }

    /** @return array{0:int,1:int,2:list<array<string,mixed>>,3:string} */
    private function paymentAmounts(Payment $payment): array
    {
        $currency = strtoupper((string) $payment->currency);
        $baseMinor = Money::toMinor((string) $payment->getMeta('base_amount', $payment->fee?->amount ?? 0), $currency);
        $cleanItems = $this->discounts->cleanAdditionalItems($payment);
        $originalTotalMinor = $baseMinor;
        foreach ($cleanItems as $item) {
            $originalTotalMinor += Money::toMinor((string) ($item['total_amount'] ?? $item['amount'] ?? 0), $currency);
        }

        return [$baseMinor, $originalTotalMinor, $cleanItems, $currency];
    }
}
