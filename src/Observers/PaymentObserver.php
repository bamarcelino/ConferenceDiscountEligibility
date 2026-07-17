<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Observers;

use App\Models\Payment;
use ConferenceDiscountEligibility\Services\CouponRedemptionService;

final class PaymentObserver
{
    public function updated(Payment $payment): void
    {
        if (! $payment->wasChanged('paid_at') || $payment->paid_at === null) {
            return;
        }

        app(CouponRedemptionService::class)->consumeForPayment($payment, 'payment_observer');
    }
}
