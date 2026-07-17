<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Support;

final class PaypalAmountContract
{
    public static function format(int $minor, string $currency): string
    {
        return Money::decimal($minor, $currency);
    }

    public static function matches(int $minor, string $currency, string $paypalAmount): bool
    {
        return hash_equals(self::format($minor, $currency), number_format((float) $paypalAmount, 2, '.', ''));
    }
}
