<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Support;

final class CsvSanitizer
{
    public static function safeCell(mixed $value): string
    {
        $value = (string) $value;
        $trimmed = ltrim($value);
        if ($trimmed !== '' && in_array($trimmed[0], ['=', '+', '-', '@'], true)) { return "'" . $value; }
        return $value;
    }
}
