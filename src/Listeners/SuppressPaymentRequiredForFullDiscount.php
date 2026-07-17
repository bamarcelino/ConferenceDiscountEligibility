<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Listeners;

use App\Models\Payment;
use App\Notifications\ParticipantPayment;
use App\Notifications\SubmissionPayment;
use ConferenceDiscountEligibility\Support\FullDiscountPolicy;
use Illuminate\Notifications\Events\NotificationSending;

final class SuppressPaymentRequiredForFullDiscount
{
    public function handle(NotificationSending $event): ?bool
    {
        $payment = match (true) {
            $event->notification instanceof ParticipantPayment => $event->notification->participant->payment,
            $event->notification instanceof SubmissionPayment => $event->notification->submission->payment,
            default => null,
        };

        if (! $payment instanceof Payment) {
            return null;
        }

        if (
            $payment->isPaid()
            && (string) $payment->payment_method === FullDiscountPolicy::PAYMENT_METHOD
        ) {
            return false;
        }

        return null;
    }
}
