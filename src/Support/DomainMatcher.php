<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Support;

final class DomainMatcher
{
    public static function normalize(?string $domain): ?string
    {
        if ($domain === null) { return null; }
        $normalized = mb_strtolower(rtrim(trim($domain), '.'));
        if ($normalized === '') { return null; }
        if (function_exists('idn_to_ascii')) {
            $flags = defined('IDNA_DEFAULT') ? IDNA_DEFAULT : 0;
            $variant = defined('INTL_IDNA_VARIANT_UTS46') ? INTL_IDNA_VARIANT_UTS46 : 1;
            $ascii = idn_to_ascii($normalized, $flags, $variant);
            if ($ascii !== false) { $normalized = mb_strtolower($ascii); }
        }
        if (strlen($normalized) > 253) { return null; }
        $labels = explode('.', $normalized);
        if (count($labels) < 2) { return null; }
        foreach ($labels as $label) {
            if ($label === '' || strlen($label) > 63 || ! preg_match('/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/', $label)) { return null; }
        }
        return $normalized;
    }

    public static function matches(string $candidate, string $ruleDomain, bool $includeSubdomains): bool
    {
        $candidate = self::normalize($candidate);
        $ruleDomain = self::normalize($ruleDomain);
        if ($candidate === null || $ruleDomain === null) { return false; }
        if (hash_equals($ruleDomain, $candidate)) { return true; }
        return $includeSubdomains && str_ends_with($candidate, '.' . $ruleDomain);
    }

    /** @return list<string> */
    public static function suffixes(string $domain): array
    {
        $normalized = self::normalize($domain);
        if ($normalized === null) { return []; }
        $labels = explode('.', $normalized);
        $suffixes = [];
        for ($index = 0; $index < count($labels) - 1; $index++) {
            $suffixes[] = implode('.', array_slice($labels, $index));
        }
        return array_values(array_unique($suffixes));
    }
}
