<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Support;

use InvalidArgumentException;

final class FullDiscountPolicy
{
    public const PAYMENT_METHOD = 'full_discount';

    public static function shouldSettle(int $finalTotalMinor): bool
    {
        if ($finalTotalMinor < 0) {
            throw new InvalidArgumentException('The final payment total cannot be negative.');
        }

        return $finalTotalMinor === 0;
    }
}
