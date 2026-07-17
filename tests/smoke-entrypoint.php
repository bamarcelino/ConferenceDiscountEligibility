<?php

declare(strict_types=1);

namespace Filament { class Panel {} }
namespace Illuminate\Database\Eloquent { class Model {} }
namespace Carbon { class Carbon {} }
namespace App\Interfaces { interface HasPayment {} }
namespace App\Models {
    class PaymentFee { public mixed $scheduled_conference_id = null; public mixed $currency = 'EUR'; public mixed $amount = 0; }
    class User {}
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
        ) {}
    }
}
namespace App\Classes {
    use Filament\Panel;
    class Plugin
    {
        public function boot() {}
        public function onPanel(Panel $panel): void {}
    }
}
namespace {
    require_once dirname(__DIR__) . '/vendor/autoload.php';

    $manager = new ReflectionClass(ConferenceDiscountEligibility\Managers\DiscountAwarePaymentManager::class);
    if (! $manager->isSubclassOf(App\Managers\PaymentManager::class)) {
        fwrite(STDERR, "DiscountAwarePaymentManager is not a PaymentManager subclass.\n");
        exit(1);
    }

    if (! ConferenceDiscountEligibility\Support\DiscountablePaymentTypes::contains(App\Managers\PaymentManager::TYPE_PARTICIPANT_FEE)) {
        fwrite(STDERR, "Participant payments are not marked discountable.\n");
        exit(1);
    }
    if (! ConferenceDiscountEligibility\Support\DiscountablePaymentTypes::contains(App\Managers\PaymentManager::TYPE_SUBMISSION_FEE)) {
        fwrite(STDERR, "Submission payments are not marked discountable.\n");
        exit(1);
    }
    if (ConferenceDiscountEligibility\Support\DiscountablePaymentTypes::contains(999)) {
        fwrite(STDERR, "Unknown payment type was incorrectly accepted.\n");
        exit(1);
    }

    $plugin = require dirname(__DIR__) . '/index.php';
    if (! $plugin instanceof ConferenceDiscountEligibility\ConferenceDiscountEligibilityPlugin) {
        fwrite(STDERR, "index.php did not return the plugin instance.\n");
        exit(1);
    }

    echo "Entrypoint, PaymentManager signature, and participant/submission payment-type smoke tests passed.\n";
}
