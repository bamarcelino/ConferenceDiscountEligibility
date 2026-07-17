<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Support;

use App\Managers\PaymentManager;

final class CouponPaymentTypes
{
    public const PARTICIPANT = 'participant';
    public const SUBMISSION = 'submission';

    /** @return list<string> */
    public static function all(): array
    {
        return [self::PARTICIPANT, self::SUBMISSION];
    }

    public static function fromNative(int $type): ?string
    {
        return match ($type) {
            PaymentManager::TYPE_PARTICIPANT_FEE => self::PARTICIPANT,
            PaymentManager::TYPE_SUBMISSION_FEE => self::SUBMISSION,
            default => null,
        };
    }

    /** @param list<string>|null $eligible */
    public static function accepts(int $nativeType, ?array $eligible): bool
    {
        $type = self::fromNative($nativeType);
        if ($type === null) {
            return false;
        }

        $eligible = array_values(array_unique(array_filter(array_map('strval', $eligible ?? self::all()))));

        return in_array($type, $eligible, true);
    }
}
