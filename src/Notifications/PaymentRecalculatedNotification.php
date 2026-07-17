<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class PaymentRecalculatedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Payment $payment) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('ConferenceDiscountEligibility::messages.payment_recalculated_subject'))
            ->line(__('ConferenceDiscountEligibility::messages.payment_recalculated_body'))
            ->action(__('ConferenceDiscountEligibility::messages.view_payment'), $this->payment->getPaymentDetailUrl());
    }
}
