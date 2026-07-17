<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Services;

use App\Models\Payment;
use Carbon\CarbonInterface;
use ConferenceDiscountEligibility\Data\EligibilityCandidate;
use ConferenceDiscountEligibility\Enums\CouponStatus;
use ConferenceDiscountEligibility\Enums\EligibilityType;
use ConferenceDiscountEligibility\Models\ConferenceDiscountCoupon;
use ConferenceDiscountEligibility\Models\ConferenceDiscountCouponRedemption;
use ConferenceDiscountEligibility\Support\CouponCode;
use ConferenceDiscountEligibility\Support\CouponPaymentTypes;
use ConferenceDiscountEligibility\Support\RuleValidity;

final class CouponEligibilityService
{
    /**
     * @return array{coupon:ConferenceDiscountCoupon|null,candidate:EligibilityCandidate}
     */
    public function evaluateCode(Payment $payment, string $code, CarbonInterface $at, bool $lock = false): array
    {
        $codeHash = CouponCode::hash($code);
        $query = ConferenceDiscountCoupon::query()
            ->where('scheduled_conference_id', $payment->scheduled_conference_id)
            ->where('code_hash', $codeHash);
        if ($lock) {
            $query->lockForUpdate();
        }

        $coupon = $query->first();
        if ($coupon === null) {
            return [
                'coupon' => null,
                'candidate' => new EligibilityCandidate(
                    type: EligibilityType::Coupon,
                    id: 0,
                    percentageBasisPoints: 0,
                    reason: '',
                    eligible: false,
                    rejectionReason: 'coupon_not_found',
                    context: ['code_fingerprint' => substr($codeHash, 0, 12)],
                ),
            ];
        }

        return [
            'coupon' => $coupon,
            'candidate' => $this->candidateForCoupon($payment, $coupon, $at, $lock),
        ];
    }

    public function candidateForCoupon(
        Payment $payment,
        ConferenceDiscountCoupon $coupon,
        CarbonInterface $at,
        bool $lock = false,
    ): EligibilityCandidate {
        $reason = RuleValidity::rejectionReason(
            (bool) $coupon->active,
            $coupon->valid_from,
            $coupon->valid_until,
            $coupon->maximum_uses,
            (int) $coupon->uses_count,
            $at,
        );

        if ((int) $coupon->scheduled_conference_id !== (int) $payment->scheduled_conference_id) {
            $reason = 'different_scheduled_conference';
        } elseif (! CouponPaymentTypes::accepts((int) $payment->type, $coupon->eligible_payment_types)) {
            $reason = 'payment_type_not_eligible';
        } elseif (
            is_array($coupon->eligible_payment_fee_ids)
            && $coupon->eligible_payment_fee_ids !== []
            && ! in_array((int) $payment->payment_fee_id, array_map('intval', $coupon->eligible_payment_fee_ids), true)
        ) {
            $reason = 'payment_fee_not_eligible';
        }

        $existingForPayment = ConferenceDiscountCouponRedemption::query()
            ->where('payment_id', $payment->getKey())
            ->where('coupon_campaign_id', $coupon->getKey())
            ->whereIn('status', [CouponStatus::Reserved->value, CouponStatus::Consumed->value]);
        if ($lock) {
            $existingForPayment->lockForUpdate();
        }
        $alreadyClaimedByPayment = $existingForPayment->exists();

        if ($reason === 'maximum_uses_reached' && $alreadyClaimedByPayment) {
            $reason = null;
        }

        $userId = (int) ($payment->user_id ?? 0);
        $userClaims = 0;
        if ($reason === null && $userId > 0 && ! $alreadyClaimedByPayment) {
            $claimQuery = ConferenceDiscountCouponRedemption::query()
                ->where('coupon_campaign_id', $coupon->getKey())
                ->where('user_id', $userId)
                ->whereIn('status', [CouponStatus::Reserved->value, CouponStatus::Consumed->value])
                ->where('payment_id', '!=', $payment->getKey());
            $userClaims = $lock
                ? $claimQuery->lockForUpdate()->get()->count()
                : $claimQuery->count();
            if ($userClaims >= (int) $coupon->per_user_limit) {
                $reason = 'per_user_limit_reached';
            }
        }

        return new EligibilityCandidate(
            type: EligibilityType::Coupon,
            id: (int) $coupon->getKey(),
            percentageBasisPoints: (int) $coupon->percentage_basis_points,
            reason: (string) $coupon->reason,
            eligible: $reason === null,
            rejectionReason: $reason,
            context: [
                'campaign_name' => (string) $coupon->name,
                'code_hint' => (string) $coupon->code_hint,
                'eligible_payment_types' => $coupon->eligible_payment_types,
                'eligible_payment_fee_ids' => $coupon->eligible_payment_fee_ids,
                'per_user_limit' => (int) $coupon->per_user_limit,
                'user_active_claims' => $userClaims,
                'already_claimed_by_payment' => $alreadyClaimedByPayment,
            ],
        );
    }
}
