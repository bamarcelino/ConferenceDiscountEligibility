<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Support;

use App\Managers\PaymentManager;
use App\Models\Payment;

final class PaymentSafety
{
    public static function canRecalculate(Payment $payment): bool
    {
        if ($payment->type !== PaymentManager::TYPE_PARTICIPANT_FEE || $payment->isPaid()) { return false; }
        if (filled($payment->payment_method)) { return false; }
        foreach (['paypal_payment_id','paypal_token','paypal_payer_id'] as $key) {
            if (filled($payment->getMeta($key))) { return false; }
        }
        return true;
    }
}
