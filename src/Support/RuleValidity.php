<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Support;

use DateTimeInterface;

final class RuleValidity
{
    public static function rejectionReason(bool $active, ?DateTimeInterface $validFrom, ?DateTimeInterface $validUntil, ?int $maximumUses, int $usesCount, DateTimeInterface $at): ?string
    {
        if (! $active) { return 'inactive'; }
        if ($validFrom !== null && $at < $validFrom) { return 'not_yet_valid'; }
        if ($validUntil !== null && $at > $validUntil) { return 'expired'; }
        if ($maximumUses !== null && $usesCount >= $maximumUses) { return 'usage_limit_reached'; }
        return null;
    }
}
