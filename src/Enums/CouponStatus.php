<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Enums;

enum CouponStatus: string
{
    case Reserved = 'reserved';
    case Consumed = 'consumed';
    case Released = 'released';
    case Revoked = 'revoked';

    public function isActiveClaim(): bool
    {
        return in_array($this, [self::Reserved, self::Consumed], true);
    }
}
