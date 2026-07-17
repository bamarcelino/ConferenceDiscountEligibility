<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Support;

final class EmailNormalizer
{
    public static function normalize(?string $email): ?string
    {
        if ($email === null) { return null; }
        $normalized = mb_strtolower(trim($email));
        if ($normalized === '' || filter_var($normalized, FILTER_VALIDATE_EMAIL) === false) { return null; }
        return $normalized;
    }

    public static function domain(?string $email): ?string
    {
        $normalized = self::normalize($email);
        if ($normalized === null) { return null; }
        $position = strrpos($normalized, '@');
        return $position === false ? null : DomainMatcher::normalize(substr($normalized, $position + 1));
    }
}
