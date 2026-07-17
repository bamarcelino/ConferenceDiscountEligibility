<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Support;

use InvalidArgumentException;

final class Money
{
    private const NON_TWO_DECIMAL_CURRENCIES = [
        'BIF','CLP','DJF','GNF','HUF','ISK','JPY','KMF','KRW','PYG','RWF','TWD','UGX','VND','VUV','XAF','XOF','XPF',
        'BHD','IQD','JOD','KWD','LYD','OMR','TND',
    ];

    public static function assertSupportedCurrency(string $currency): string
    {
        $currency = strtoupper(trim($currency));
        if (! preg_match('/^[A-Z]{3}$/', $currency)) { throw new InvalidArgumentException('Invalid ISO 4217 currency code.'); }
        if (in_array($currency, self::NON_TWO_DECIMAL_CURRENCIES, true)) {
            throw new InvalidArgumentException("Currency {$currency} is not supported by calculation version 1.");
        }
        return $currency;
    }

    public static function toMinor(int|float|string $amount, string $currency): int
    {
        self::assertSupportedCurrency($currency);
        $decimal = is_float($amount) ? number_format($amount, 8, '.', '') : trim((string) $amount);
        if (! preg_match('/^(-?)(\d+)(?:\.(\d+))?$/', $decimal, $matches)) { throw new InvalidArgumentException('Invalid decimal monetary amount.'); }
        $negative = $matches[1] === '-';
        $whole = ltrim($matches[2], '0');
        $whole = $whole === '' ? '0' : $whole;
        if (strlen($whole) > 16) { throw new InvalidArgumentException('Monetary amount is outside the supported integer range.'); }
        $fraction = $matches[3] ?? '';
        $minor = ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
        if ((int) ($fraction[2] ?? '0') >= 5) { $minor++; }
        return $negative ? -$minor : $minor;
    }

    public static function decimal(int $minor, string $currency): string
    {
        self::assertSupportedCurrency($currency);
        $negative = $minor < 0;
        $absolute = abs($minor);
        $value = intdiv($absolute, 100) . '.' . str_pad((string) ($absolute % 100), 2, '0', STR_PAD_LEFT);
        return $negative ? '-' . $value : $value;
    }

    public static function decimalFloat(int $minor, string $currency): float
    {
        return (float) self::decimal($minor, $currency);
    }

    public static function multiplyBasisPoints(int $minor, int $basisPoints): int
    {
        if ($minor < 0) { throw new InvalidArgumentException('Discount calculations require a non-negative amount.'); }
        Percentage::assertBasisPoints($basisPoints, true);
        return intdiv(($minor * $basisPoints) + 5000, 10000);
    }
}
