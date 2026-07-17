<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Services;

use App\Models\Payment;
use ConferenceDiscountEligibility\Models\ConferenceDiscountPaymentSnapshot;
use ConferenceDiscountEligibility\Notifications\PaymentRecalculatedNotification;
use ConferenceDiscountEligibility\Support\Money;
use ConferenceDiscountEligibility\Support\PaymentSafety;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class UnpaidPaymentRecalculator
{
    public function __construct(
        private readonly PaymentDiscountService $discounts,
        private readonly SnapshotService $snapshots,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function recalculate(Payment $payment, bool $notify = false, string $origin = 'admin_recalculation'): Payment
    {
        $updated = DB::transaction(function () use ($payment, $origin): Payment {
            $locked = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());
            if (! PaymentSafety::canRecalculate($locked)) {
                throw new RuntimeException(__('ConferenceDiscountEligibility::messages.recalculation_not_safe'));
            }

            $currency = strtoupper((string) $locked->currency);
            $baseMinor = Money::toMinor((string) $locked->getMeta('base_amount', $locked->fee?->amount ?? 0), $currency);
            $cleanItems = $this->discounts->cleanAdditionalItems($locked);
            $originalTotalMinor = $baseMinor;
            foreach ($cleanItems as $item) {
                $originalTotalMinor += Money::toMinor((string) ($item['total_amount'] ?? $item['amount'] ?? 0), $currency);
            }
            $prepared = $this->discounts->prepare(
                (int) $locked->scheduled_conference_id,
                $locked->user,
                $baseMinor,
                $originalTotalMinor,
                $cleanItems,
                $currency,
                true,
            );
            $oldSnapshot = ConferenceDiscountPaymentSnapshot::query()->where('payment_id', $locked->getKey())->first();
            $locked->forceFill(['amount' => Money::decimalFloat($prepared->calculation->finalTotalMinor, $currency)])->save();
            $locked->setMeta('additional_items', $prepared->additionalItems);
            $snapshot = $this->snapshots->record($locked, $prepared, $origin);
            $this->auditLogger->log(
                action: $prepared->selection->hasDiscount() ? 'payment_recalculated' : 'payment_recalculated_without_discount',
                scheduledConferenceId: (int) $locked->scheduled_conference_id,
                auditable: $locked,
                affectedUserId: $locked->user_id,
                oldValues: $oldSnapshot?->toArray(),
                newValues: $snapshot->toArray(),
                context: ['evaluated_rules' => $prepared->selection->evaluatedAsArray()],
                origin: $origin,
            );
            return $locked->refresh();
        });

        if ($notify && $updated->user !== null) {
            $updated->ensureInvoice();
            $updated->user->notify(new PaymentRecalculatedNotification($updated));
        }
        return $updated;
    }

    public function reapplySnapshot(Payment $payment): ?Payment
    {
        return DB::transaction(function () use ($payment): ?Payment {
            $locked = Payment::query()->lockForUpdate()->find($payment->getKey());
            if ($locked === null || ! PaymentSafety::canRecalculate($locked)) { return null; }
            $snapshot = ConferenceDiscountPaymentSnapshot::query()->where('payment_id', $locked->getKey())->lockForUpdate()->first();
            if ($snapshot === null || (int) $snapshot->discount_percentage_basis_points <= 0) { return null; }

            $currency = strtoupper((string) $locked->currency);
            $baseMinor = Money::toMinor((string) $locked->getMeta('base_amount', $locked->fee?->amount ?? 0), $currency);
            $cleanItems = $this->discounts->cleanAdditionalItems($locked);
            $originalTotalMinor = $baseMinor;
            foreach ($cleanItems as $item) { $originalTotalMinor += Money::toMinor((string) ($item['total_amount'] ?? $item['amount'] ?? 0), $currency); }
            $prepared = $this->discounts->prepareFromSnapshot($snapshot, $baseMinor, $originalTotalMinor, $cleanItems, $currency);
            $locked->forceFill(['amount' => Money::decimalFloat($prepared->calculation->finalTotalMinor, $currency)])->save();
            $locked->setMeta('additional_items', $prepared->additionalItems);
            $this->snapshots->record($locked, $prepared, 'native_fee_edit');
            return $locked->refresh();
        });
    }
}
