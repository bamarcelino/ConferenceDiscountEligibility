<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Support;

use InvalidArgumentException;

final class Percentage
{
    public static function percentToBasisPoints(int|float|string $percentage): int
    {
        $value = str_replace(',', '.', trim((string) $percentage));
        if ($value === '' || ! is_numeric($value)) { throw new InvalidArgumentException('Discount percentage must be numeric.'); }
        $basisPoints = (int) round((float) $value * 100, 0, PHP_ROUND_HALF_UP);
        self::assertBasisPoints($basisPoints);
        return $basisPoints;
    }

    public static function assertBasisPoints(int $basisPoints, bool $allowZero = false): void
    {
        $minimum = $allowZero ? 0 : 1;
        if ($basisPoints < $minimum || $basisPoints > 10000) {
            throw new InvalidArgumentException('Discount percentage must be between 0.01 and 100 percent.');
        }
    }

    public static function format(int $basisPoints): string
    {
        self::assertBasisPoints($basisPoints, true);
        return rtrim(rtrim(number_format($basisPoints / 100, 2, '.', ''), '0'), '.');
    }
}
