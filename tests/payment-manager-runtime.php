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
    final class FakeCalculation
    {
        public function __construct(public int $finalTotalMinor) {}
    }
    final class FakePrepared
    {
        public FakeSelection $selection;
        public FakeCalculation $calculation;
        public array $additionalItems;

        public function __construct(int $finalTotalMinor)
        {
            $this->selection = new FakeSelection();
            $this->calculation = new FakeCalculation($finalTotalMinor);
            $this->additionalItems = [[
                'key' => '__conference_discount_eligibility__',
                'amount' => $finalTotalMinor === 0 ? -25.0 : -10.0,
                'total_amount' => $finalTotalMinor === 0 ? -25.0 : -10.0,
            ]];
        }
    }
    class PaymentDiscountService
    {
        public function __construct(private readonly int $finalTotalMinor = 1500) {}

        public function prepare(int $conferenceId, mixed $user, int $baseMinor, int $totalMinor, array $items, string $currency, bool $lock): FakePrepared
        {
            if ($conferenceId !== 7 || $baseMinor !== 2500 || $totalMinor !== 2500 || $currency !== 'EUR' || ! $lock) {
                throw new \RuntimeException('Unexpected payment preparation arguments.');
            }
            return new FakePrepared($this->finalTotalMinor);
        }
    }
    class SnapshotService
    {
        public function record(object $payment, FakePrepared $prepared): object
        {
            return new class($prepared->calculation->finalTotalMinor) {
                public function __construct(private readonly int $finalTotalMinor) {}
                public function toArray(): array { return ['final_total_minor' => $this->finalTotalMinor]; }
            };
        }
    }
    class AuditLogger { public function log(...$arguments): void {} }
    class FullDiscountSettlementService
    {
        public int $calls = 0;
        public ?int $lastFinalTotalMinor = null;

        public function settleIfZero(object $payment, int $finalTotalMinor, string $currency, string $origin, ?int $paidByUserId = null): object
        {
            $this->calls++;
            $this->lastFinalTotalMinor = $finalTotalMinor;
            if ($currency !== 'EUR' || $origin !== 'payment_queue' || $paidByUserId !== 44) {
                throw new \RuntimeException('Unexpected full-discount settlement arguments.');
            }
            return $payment;
        }
    }
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
        $settlement = new ConferenceDiscountEligibility\Services\FullDiscountSettlementService();
        $manager = new ConferenceDiscountEligibility\Managers\DiscountAwarePaymentManager(
            new ConferenceDiscountEligibility\Services\PaymentDiscountService(1500),
            new ConferenceDiscountEligibility\Services\SnapshotService(),
            new ConferenceDiscountEligibility\Services\AuditLogger(),
            $settlement,
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
        if ($settlement->calls !== 1 || $settlement->lastFinalTotalMinor !== 1500) {
            fwrite(STDERR, "Positive payment settlement contract was not evaluated for payment type {$type}.\n");
            exit(1);
        }

        $zeroSettlement = new ConferenceDiscountEligibility\Services\FullDiscountSettlementService();
        $zeroManager = new ConferenceDiscountEligibility\Managers\DiscountAwarePaymentManager(
            new ConferenceDiscountEligibility\Services\PaymentDiscountService(0),
            new ConferenceDiscountEligibility\Services\SnapshotService(),
            new ConferenceDiscountEligibility\Services\AuditLogger(),
            $zeroSettlement,
        );
        $zeroManager->queue($model, $fee, $user, $type, 'Full discount', '/payment');
        if ($zeroManager->lastAmount !== 0.0 || $zeroSettlement->calls !== 1 || $zeroSettlement->lastFinalTotalMinor !== 0) {
            fwrite(STDERR, "Zero-total settlement was not delegated for payment type {$type}.\n");
            exit(1);
        }
    }

    echo "Participant and submission queue runtime simulation passed for 40% and 100% discounts.\n";
}
