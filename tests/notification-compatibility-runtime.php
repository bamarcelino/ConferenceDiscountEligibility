<?php

declare(strict_types=1);

namespace App\Models {
    final class PaymentQuery
    {
        public function find(int $paymentId): ?Payment
        {
            return Payment::$records[$paymentId] ?? null;
        }
    }

    class Payment
    {
        /** @var array<int, self> */
        public static array $records = [];

        public function __construct(
            private readonly bool $paid,
            public ?string $payment_method,
        ) {}

        public static function query(): PaymentQuery
        {
            return new PaymentQuery();
        }

        public function isPaid(): bool
        {
            return $this->paid;
        }
    }
}

namespace App\Notifications {
    class ParticipantPayment
    {
        public int $paymentId;
        public object $participant;
    }

    class SubmissionPayment
    {
        public int $paymentId;
        public object $submission;
    }
}

namespace Illuminate\Notifications\Events {
    final class NotificationSending
    {
        public function __construct(public object $notification) {}
    }
}

namespace ConferenceDiscountEligibility\Tests {
    final class OtherNotification {}
}

namespace {
    require_once dirname(__DIR__) . '/vendor/autoload.php';

    use App\Models\Payment;
    use App\Notifications\ParticipantPayment;
    use App\Notifications\SubmissionPayment;
    use ConferenceDiscountEligibility\Listeners\SuppressPaymentRequiredForFullDiscount;
    use ConferenceDiscountEligibility\Tests\OtherNotification;
    use Illuminate\Notifications\Events\NotificationSending;

    $fullDiscount = new Payment(true, 'full_discount');
    $pending = new Payment(false, null);
    Payment::$records = [51 => $fullDiscount, 52 => $pending];
    $listener = new SuppressPaymentRequiredForFullDiscount();

    $participant15 = new ParticipantPayment();
    $participant15->paymentId = 51;
    if ($listener->handle(new NotificationSending($participant15)) !== false) {
        fwrite(STDERR, "Leconfe 1.5 participant payment notification was not suppressed.\n");
        exit(1);
    }

    $submission15 = new SubmissionPayment();
    $submission15->paymentId = 52;
    if ($listener->handle(new NotificationSending($submission15)) !== null) {
        fwrite(STDERR, "A pending Leconfe 1.5 submission notification was incorrectly suppressed.\n");
        exit(1);
    }

    $participant146 = new ParticipantPayment();
    $participant146->participant = (object) ['payment' => $fullDiscount];
    if ($listener->handle(new NotificationSending($participant146)) !== false) {
        fwrite(STDERR, "Leconfe 1.4.6 participant payment notification was not suppressed.\n");
        exit(1);
    }

    $submission146 = new SubmissionPayment();
    $submission146->submission = (object) ['payment' => $fullDiscount];
    if ($listener->handle(new NotificationSending($submission146)) !== false) {
        fwrite(STDERR, "Leconfe 1.4.6 submission payment notification was not suppressed.\n");
        exit(1);
    }

    if ($listener->handle(new NotificationSending(new OtherNotification())) !== null) {
        fwrite(STDERR, "An unrelated notification was incorrectly suppressed.\n");
        exit(1);
    }

    echo "Leconfe 1.4.6 and 1.5.0 payment-notification runtime simulation passed.\n";
}
