<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Data;

use App\Models\Payment;

final class CouponApplicationResult
{
    public function __construct(
        public Payment $payment,
        public string $status,
        public string $message,
        public bool $changed,
        public bool $couponSelected,
    ) {}
}
