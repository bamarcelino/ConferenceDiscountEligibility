<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Enums;

enum DiscountScope: string
{
    case BaseRegistrationFeeOnly = 'base_only';
    case BaseFeeAndEligibleAddOns = 'base_and_eligible_add_ons';

    public function label(): string
    {
        return match ($this) {
            self::BaseRegistrationFeeOnly => __('ConferenceDiscountEligibility::messages.scope_base_only'),
            self::BaseFeeAndEligibleAddOns => __('ConferenceDiscountEligibility::messages.scope_base_and_add_ons'),
        };
    }
}
