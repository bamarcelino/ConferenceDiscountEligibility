<?php

declare(strict_types=1);

namespace Filament { class Panel {} }
namespace Carbon { class Carbon {} }
namespace Illuminate\Database\Eloquent { class Model {} }
namespace Illuminate\Support\Facades {
    final class Log
    {
        public static function warning(string $message): void {}
    }
}
namespace App\Interfaces { interface HasPayment {} }
namespace App\Models {
    class Payment {}
    class PaymentFee {}
    class User {}
}
namespace App\Managers {
    use App\Interfaces\HasPayment;
    use App\Models\Payment;
    use App\Models\PaymentFee;
    use App\Models\User;
    use Carbon\Carbon;
    use Illuminate\Database\Eloquent\Model;

    class PaymentManager
    {
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

        public function fulfillQueued(Payment $payment, string $paymentMethod, ?int $userId = null) {}
    }
}
namespace ConferenceDiscountEligibility\Tests {
    final class RuntimeVersion
    {
        public static ?string $value = null;
    }
}
namespace {
    function base_path(string $path = ''): string
    {
        return sys_get_temp_dir() . '/conference-discount-eligibility-missing-' . $path;
    }

    function config(string $key): ?string
    {
        return ConferenceDiscountEligibility\Tests\RuntimeVersion::$value;
    }

    require_once dirname(__DIR__) . '/vendor/autoload.php';

    use ConferenceDiscountEligibility\Services\CompatibilityGuard;
    use ConferenceDiscountEligibility\Tests\RuntimeVersion;

    $guard = new CompatibilityGuard();
    foreach (['1.4.6', '1.5.0', '1.5.1'] as $supported) {
        RuntimeVersion::$value = $supported;
        $guard->assertCompatible();
    }

    RuntimeVersion::$value = '1.6.0';
    try {
        $guard->assertCompatible();
        fwrite(STDERR, "An unsupported Leconfe version was accepted.\n");
        exit(1);
    } catch (RuntimeException $exception) {
        if (! str_contains($exception->getMessage(), '1.5.1')) {
            fwrite(STDERR, "The unsupported-version error does not name the supported versions.\n");
            exit(1);
        }
    }

    echo "Leconfe 1.4.6/1.5.0/1.5.1 version and PaymentManager signature guard simulation passed.\n";
}
