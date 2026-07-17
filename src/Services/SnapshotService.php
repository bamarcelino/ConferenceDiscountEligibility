<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Services;

use App\Models\Payment;
use ConferenceDiscountEligibility\Data\EligibilityCandidate;
use ConferenceDiscountEligibility\Data\EligibilitySelection;
use ConferenceDiscountEligibility\Data\PreparedPaymentDiscount;
use ConferenceDiscountEligibility\Enums\EligibilityType;
use ConferenceDiscountEligibility\Models\ConferenceDiscountCoupon;
use ConferenceDiscountEligibility\Models\ConferenceDiscountDomain;
use ConferenceDiscountEligibility\Models\ConferenceDiscountEntitlement;
use ConferenceDiscountEligibility\Models\ConferenceDiscountPaymentSnapshot;

final class SnapshotService
{
    public function __construct(private readonly SettingsRepository $settings) {}

    public function record(Payment $payment, PreparedPaymentDiscount $prepared, string $origin = 'payment_creation'): ConferenceDiscountPaymentSnapshot
    {
        $winner = $prepared->selection->winner;
        $existing = ConferenceDiscountPaymentSnapshot::query()->where('payment_id', $payment->getKey())->lockForUpdate()->first();
        $metadata = is_array($existing?->metadata) ? $existing->metadata : [];
        $history = is_array($metadata['history'] ?? null) ? $metadata['history'] : [];
        if ($existing !== null) {
            $history[] = [
                'at' => now()->toIso8601String(),
                'origin' => $origin,
                'previous_final_total_minor' => (int) $existing->final_total_minor,
                'new_final_total_minor' => $prepared->calculation->finalTotalMinor,
                'previous_percentage_basis_points' => (int) $existing->discount_percentage_basis_points,
                'new_percentage_basis_points' => $winner?->percentageBasisPoints ?? 0,
                'previous_eligibility_type' => $existing->eligibility_type,
                'new_eligibility_type' => $winner?->type->value,
                'previous_eligibility_id' => $existing->eligibility_id,
                'new_eligibility_id' => $winner?->id,
            ];
        }

        $metadata = [
            'discount_scope' => $this->settings->scope((int) $payment->scheduled_conference_id)->value,
            'eligible_add_on_keys' => $this->settings->eligibleAddOnKeys((int) $payment->scheduled_conference_id),
            'evaluated_rules' => $prepared->selection->evaluatedAsArray(),
            'history' => $history,
            'origin' => $origin,
            'payment_type' => (int) $payment->type,
        ];
        $attributes = [
            'scheduled_conference_id' => $payment->scheduled_conference_id,
            'payment_id' => $payment->getKey(),
            'user_id' => $payment->user_id,
            'entitlement_id' => $winner !== null && in_array($winner->type, [EligibilityType::User, EligibilityType::Email], true) ? $winner->id : null,
            'domain_rule_id' => $winner?->type === EligibilityType::Domain ? $winner->id : null,
            'coupon_campaign_id' => $winner?->type === EligibilityType::Coupon ? $winner->id : null,
            'original_base_amount_minor' => $prepared->calculation->originalBaseMinor,
            'discount_percentage_basis_points' => $winner?->percentageBasisPoints ?? 0,
            'base_discount_amount_minor' => $prepared->calculation->baseDiscountMinor,
            'discount_amount_minor' => $prepared->calculation->discountAmountMinor,
            'final_base_amount_minor' => $prepared->calculation->finalBaseMinor,
            'add_on_amount_minor' => $prepared->calculation->addOnAmountMinor,
            'eligible_add_on_amount_minor' => $prepared->calculation->eligibleAddOnAmountMinor,
            'add_on_discount_amount_minor' => $prepared->calculation->addOnDiscountMinor,
            'original_total_minor' => $prepared->calculation->originalTotalMinor,
            'final_total_minor' => $prepared->calculation->finalTotalMinor,
            'currency' => $prepared->currency,
            'eligibility_type' => $winner?->type->value,
            'eligibility_id' => $winner?->id,
            'eligibility_reason' => $winner?->reason,
            'eligibility_snapshot_at' => $existing?->eligibility_snapshot_at ?? now(),
            'calculation_version' => DiscountCalculator::CALCULATION_VERSION,
            'metadata' => $metadata,
        ];

        $snapshot = ConferenceDiscountPaymentSnapshot::query()->updateOrCreate(['payment_id' => $payment->getKey()], $attributes);
        $this->adjustUseCount($existing, $winner);
        $payment->setMeta('conference_discount_eligibility', [
            'original_base_amount_minor' => $snapshot->original_base_amount_minor,
            'discount_percentage_basis_points' => $snapshot->discount_percentage_basis_points,
            'discount_amount_minor' => $snapshot->discount_amount_minor,
            'final_base_amount_minor' => $snapshot->final_base_amount_minor,
            'add_on_amount_minor' => $snapshot->add_on_amount_minor,
            'final_total_minor' => $snapshot->final_total_minor,
            'eligibility_type' => $snapshot->eligibility_type,
            'eligibility_id' => $snapshot->eligibility_id,
            'eligibility_reason' => $snapshot->eligibility_reason,
            'eligibility_snapshot_at' => $snapshot->eligibility_snapshot_at?->toIso8601String(),
            'calculated_currency' => $snapshot->currency,
            'calculation_version' => $snapshot->calculation_version,
        ]);

        return $snapshot;
    }

    public static function selectionFromSnapshot(ConferenceDiscountPaymentSnapshot $snapshot): EligibilitySelection
    {
        $type = EligibilityType::tryFrom((string) $snapshot->eligibility_type);
        $winner = $type === null || ! $snapshot->eligibility_id ? null : new EligibilityCandidate(
            type: $type,
            id: (int) $snapshot->eligibility_id,
            percentageBasisPoints: (int) $snapshot->discount_percentage_basis_points,
            reason: (string) $snapshot->eligibility_reason,
            eligible: true,
            context: ['snapshot' => true],
        );
        $evaluated = [];
        $rows = is_array($snapshot->metadata['evaluated_rules'] ?? null) ? $snapshot->metadata['evaluated_rules'] : [];
        foreach ($rows as $row) {
            if (! is_array($row) || ! isset($row['type'], $row['id'])) {
                continue;
            }
            $candidateType = EligibilityType::tryFrom((string) $row['type']);
            if ($candidateType === null) {
                continue;
            }
            $evaluated[] = new EligibilityCandidate(
                type: $candidateType,
                id: (int) $row['id'],
                percentageBasisPoints: (int) ($row['percentage_basis_points'] ?? 0),
                reason: (string) ($row['reason'] ?? ''),
                eligible: (bool) ($row['eligible'] ?? false),
                rejectionReason: isset($row['rejection_reason']) ? (string) $row['rejection_reason'] : null,
                context: is_array($row['context'] ?? null) ? $row['context'] : [],
            );
        }

        return new EligibilitySelection($winner, $evaluated);
    }

    private function adjustUseCount(?ConferenceDiscountPaymentSnapshot $existing, ?EligibilityCandidate $winner): void
    {
        $oldType = EligibilityType::tryFrom((string) $existing?->eligibility_type);
        $oldId = (int) ($existing?->eligibility_id ?? 0);
        $newType = $winner?->type;
        $newId = (int) ($winner?->id ?? 0);

        if ($oldType === $newType && $oldId === $newId) {
            return;
        }
        if ($oldType !== null && $oldId > 0) {
            $this->changeUse($oldType, $oldId, -1);
        }
        if ($newType !== null && $newId > 0) {
            $this->changeUse($newType, $newId, 1);
        }
    }

    private function changeUse(EligibilityType $type, int $id, int $delta): void
    {
        $query = match ($type) {
            EligibilityType::Domain => ConferenceDiscountDomain::query(),
            EligibilityType::Coupon => ConferenceDiscountCoupon::query(),
            default => ConferenceDiscountEntitlement::query(),
        };
        $model = $query->lockForUpdate()->find($id);
        if ($model === null) {
            return;
        }
        if ($delta > 0) {
            $model->increment('uses_count');

            return;
        }
        if ((int) $model->uses_count > 0) {
            $model->decrement('uses_count');
        }
    }
}
