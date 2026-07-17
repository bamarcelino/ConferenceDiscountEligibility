<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Support;

use Throwable;

final class AuditValueFormatter
{
    public static function json(mixed $value): string
    {
        if ($value === null || $value === [] || $value === '') {
            return '—';
        }

        try {
            $encoded = json_encode(
                $value,
                JSON_PRETTY_PRINT
                | JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_INVALID_UTF8_SUBSTITUTE
                | JSON_THROW_ON_ERROR,
            );

            return is_string($encoded) && $encoded !== '' ? $encoded : '—';
        } catch (Throwable) {
            return '—';
        }
    }
}
