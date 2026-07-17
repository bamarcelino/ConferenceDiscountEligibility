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
        if ($snapshot === null) { return '-'; }
        $minor = (int) $snapshot->{$field};
        if ($negative) { $minor *= -1; }
        return money((float) Money::decimal($minor, (string) $snapshot->currency), $snapshot->currency, true)->formatWithoutZeroes();
    }

    public function percentage(Payment $payment): string
    {
        $snapshot = $this->snapshot($payment);
        return $snapshot ? Percentage::format((int) $snapshot->discount_percentage_basis_points) . '%' : '-';
    }
}
