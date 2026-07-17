<?php

declare(strict_types=1);

namespace Filament { class Panel {} }
namespace Carbon { class Carbon {} }
namespace Illuminate\Database\Eloquent { class Model {} }
namespace Illuminate\Support\Facades {
    final class DB
    {
        public static function transaction(callable $callback): mixed
        {
            return $callback();
        }
    }
}
namespace App\Interfaces { interface HasPayment {} }
namespace App\Models {
    class PaymentFee
    {
        public int $scheduled_conference_id = 7;
        public string $currency = 'EUR';
        public float $amount = 25.0;
    }
    class User { public function getKey(): int { return 44; } }
}
namespace App\Managers {
    use App\Interfaces\HasPayment;
    use App\Models\PaymentFee;
    use App\Models\User;
    use Carbon\Carbon;
    use Illuminate\Database\Eloquent\Model;

    class PaymentManager
    {
        public const TYPE_PARTICIPANT_FEE = 1;
        public const TYPE_SUBMISSION_FEE = 2;

        public ?int $lastType = null;
        public ?float $lastAmount = null;
        public array $lastAdditionalItems = [];
        public ?float $lastBaseAmount = null;

        public function queue(
            Model&HasPayment $model,
            PaymentFee $paymentFee,
            ?User $user,
            int $type,
            string $title,
            string $requestUrl,
            ?string $description = null,
            ?float $amount = null,
            ?string $currency = null,
            ?Carbon $expiredAt = null,
            array $additionalItems = [],
            ?float $baseAmount = null,
        ) {
            $this->lastType = $type;
            $this->lastAmount = $amount;
            $this->lastAdditionalItems = $additionalItems;
            $this->lastBaseAmount = $baseAmount;
            return new class {
                public function getKey(): int { return 101; }
            };
        }
    }
}
namespace ConferenceDiscountEligibility\Services {
    final class FakeSelection
    {
        public array $evaluated = [];
        public function hasDiscount(): bool { return true; }
        public function evaluatedAsArray(): array { return []; }
    }
    final class FakeCalculation { public int $finalTotalMinor = 1500; }
    final class FakePrepared
    {
        public FakeSelection $selection;
        public FakeCalculation $calculation;
        public array $additionalItems = [[
            'key' => '__conference_discount_eligibility__',
            'amount' => -10.0,
            'total_amount' => -10.0,
        ]];
        public function __construct()
        {
            $this->selection = new FakeSelection();
            $this->calculation = new FakeCalculation();
        }
    }
    class PaymentDiscountService
    {
        public function prepare(int $conferenceId, mixed $user, int $baseMinor, int $totalMinor, array $items, string $currency, bool $lock): FakePrepared
        {
            if ($conferenceId !== 7 || $baseMinor !== 2500 || $totalMinor !== 2500 || $currency !== 'EUR' || ! $lock) {
                throw new \RuntimeException('Unexpected payment preparation arguments.');
            }
            return new FakePrepared();
        }
    }
    class SnapshotService { public function record(object $payment, FakePrepared $prepared): object { return new class { public function toArray(): array { return ['final_total_minor' => 1500]; } }; } }
    class AuditLogger { public function log(...$arguments): void {} }
}
namespace ConferenceDiscountEligibility\Tests {
    final class PaymentModel extends \Illuminate\Database\Eloquent\Model implements \App\Interfaces\HasPayment {}
}
namespace {
    require_once dirname(__DIR__) . '/vendor/autoload.php';

    $fee = new App\Models\PaymentFee();
    $user = new App\Models\User();
    $model = new ConferenceDiscountEligibility\Tests\PaymentModel();

    foreach ([App\Managers\PaymentManager::TYPE_PARTICIPANT_FEE, App\Managers\PaymentManager::TYPE_SUBMISSION_FEE] as $type) {
        $manager = new ConferenceDiscountEligibility\Managers\DiscountAwarePaymentManager(
            new ConferenceDiscountEligibility\Services\PaymentDiscountService(),
            new ConferenceDiscountEligibility\Services\SnapshotService(),
            new ConferenceDiscountEligibility\Services\AuditLogger(),
        );
        $manager->queue($model, $fee, $user, $type, 'Test', '/payment');
        if ($manager->lastType !== $type || $manager->lastAmount !== 15.0 || $manager->lastBaseAmount !== 25.0) {
            fwrite(STDERR, "Discounted queue arguments were not preserved for payment type {$type}.\n");
            exit(1);
        }
        if (($manager->lastAdditionalItems[0]['amount'] ?? null) !== -10.0) {
            fwrite(STDERR, "Discount line was not passed for payment type {$type}.\n");
            exit(1);
        }
    }

    echo "Participant and submission queue runtime simulation passed; both sent final amount 15.00 from base 25.00.\n";
}
