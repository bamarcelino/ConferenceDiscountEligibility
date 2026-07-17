<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Support;

use InvalidArgumentException;

final class CouponCode
{
    private const MIN_LENGTH = 4;
    private const MAX_LENGTH = 64;

    public static function normalize(string $code): string
    {
        $normalized = strtoupper((string) preg_replace('/\s+/u', '', trim($code)));

        if (
            strlen($normalized) < self::MIN_LENGTH
            || strlen($normalized) > self::MAX_LENGTH
            || preg_match('/^[A-Z0-9][A-Z0-9_-]*$/D', $normalized) !== 1
        ) {
            throw new InvalidArgumentException('Coupon code format is invalid.');
        }

        return $normalized;
    }

    public static function hash(string $code, ?string $key = null): string
    {
        $secret = $key ?? self::applicationKey();
        if ($secret === '') {
            throw new InvalidArgumentException('Application key is required to hash coupon codes.');
        }

        return hash_hmac('sha256', self::normalize($code), $secret);
    }

    public static function hint(string $code): string
    {
        $normalized = self::normalize($code);
        if (strlen($normalized) <= 10) {
            return substr($normalized, 0, 2) . str_repeat('•', max(1, strlen($normalized) - 4)) . substr($normalized, -2);
        }

        return substr($normalized, 0, 6) . '…' . substr($normalized, -4);
    }

    public static function generate(string $prefix = 'CDE'): string
    {
        $safePrefix = strtoupper((string) preg_replace('/[^A-Z0-9]/i', '', $prefix));
        $safePrefix = substr($safePrefix !== '' ? $safePrefix : 'CDE', 0, 12);
        $hex = strtoupper(bin2hex(random_bytes(16)));

        return $safePrefix . '-' . implode('-', str_split($hex, 8));
    }

    private static function applicationKey(): string
    {
        $key = (string) config('app.key');
        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            if ($decoded !== false) {
                return $decoded;
            }
        }

        return $key;
    }
}
