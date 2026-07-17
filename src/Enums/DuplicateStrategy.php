<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Enums;

enum DuplicateStrategy: string
{
    case Error = 'error';
    case Update = 'update';
    case Ignore = 'ignore';
}
