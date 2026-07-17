<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Support;

use App\Managers\PaymentManager;

final class DiscountablePaymentTypes
{
    /** @return list<int> */
    public static function all(): array
    {
        return [
            PaymentManager::TYPE_PARTICIPANT_FEE,
            PaymentManager::TYPE_SUBMISSION_FEE,
        ];
    }

    public static function contains(int $type): bool
    {
        return in_array($type, self::all(), true);
    }
}
