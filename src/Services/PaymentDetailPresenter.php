<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Services;

use App\Models\Payment;
use ConferenceDiscountEligibility\Models\ConferenceDiscountPaymentSnapshot;
use ConferenceDiscountEligibility\Support\Money;
use ConferenceDiscountEligibility\Support\Percentage;

final class PaymentDetailPresenter
{
    /** @var array<int, ConferenceDiscountPaymentSnapshot|null> */
    private array $cache = [];

    public function snapshot(Payment $payment): ?ConferenceDiscountPaymentSnapshot
    {
        return $this->cache[$payment->getKey()] ??= ConferenceDiscountPaymentSnapshot::query()
            ->where('payment_id', $payment->getKey())
            ->first();
    }

    public function visible(Payment $payment): bool
    {
        return $this->snapshot($payment) !== null;
    }

    public function money(Payment $payment, string $field, bool $negative = false): string
    {
        $snapshot = $this->snapshot($payment);
        if ($snapshot === null) {
            return '-';
        }

        $minor = (int) $snapshot->{$field};
        if ($negative) {
            $minor *= -1;
        }

        return money(
            (float) Money::decimal($minor, (string) $snapshot->currency),
            $snapshot->currency,
            true,
        )->formatWithoutZeroes();
    }

    public function percentage(Payment $payment): string
    {
        $snapshot = $this->snapshot($payment);

        return $snapshot
            ? Percentage::format((int) $snapshot->discount_percentage_basis_points) . '%'
            : '-';
    }

    public function identityEvidence(Payment $payment): string
    {
        $snapshot = $this->snapshot($payment);

        return $snapshot === null ? '—' : $this->identityEvidenceFromSnapshot($snapshot);
    }

    public function identityEvidenceFromSnapshot(ConferenceDiscountPaymentSnapshot $snapshot): string
    {
        if ($snapshot->eligibility_type !== 'domain') {
            return '—';
        }

        $metadata = is_array($snapshot->metadata) ? $snapshot->metadata : [];
        $rules = is_array($metadata['evaluated_rules'] ?? null)
            ? $metadata['evaluated_rules']
            : [];

        foreach ($rules as $rule) {
            if (! is_array($rule)
                || ($rule['type'] ?? null) !== 'domain'
                || (int) ($rule['id'] ?? 0) !== (int) $snapshot->eligibility_id
                || ! (bool) ($rule['eligible'] ?? false)) {
                continue;
            }

            $context = is_array($rule['context'] ?? null) ? $rule['context'] : [];

            return match ($context['identity_verification_method'] ?? null) {
                'verified_email' => __('ConferenceDiscountEligibility::messages.identity_verified_email'),
                'confirmed_author' => __('ConferenceDiscountEligibility::messages.identity_confirmed_author'),
                default => '—',
            };
        }

        return '—';
    }
}
