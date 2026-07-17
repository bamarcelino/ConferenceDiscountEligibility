<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Enums;

enum EligibilityType: string
{
    case User = 'user';
    case Email = 'email';
    case Domain = 'domain';

    public function priority(): int
    {
        return match ($this) {
            self::User => 30,
            self::Email => 20,
            self::Domain => 10,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::User => __('ConferenceDiscountEligibility::messages.type_user'),
            self::Email => __('ConferenceDiscountEligibility::messages.type_email'),
            self::Domain => __('ConferenceDiscountEligibility::messages.type_domain'),
        };
    }
}
