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
        $payment = $this->resolvePayment($event->notification);

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

    private function resolvePayment(object $notification): ?Payment
    {
        if (! $notification instanceof ParticipantPayment && ! $notification instanceof SubmissionPayment) {
            return null;
        }

        if (property_exists($notification, 'paymentId') && isset($notification->paymentId)) {
            $paymentId = (int) $notification->paymentId;

            return $paymentId > 0 ? Payment::query()->find($paymentId) : null;
        }

        if ($notification instanceof ParticipantPayment
            && property_exists($notification, 'participant')
            && isset($notification->participant)) {
            $payment = $notification->participant->payment ?? null;

            return $payment instanceof Payment ? $payment : null;
        }

        if ($notification instanceof SubmissionPayment
            && property_exists($notification, 'submission')
            && isset($notification->submission)) {
            $payment = $notification->submission->payment ?? null;

            return $payment instanceof Payment ? $payment : null;
        }

        return null;
    }
}
