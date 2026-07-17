<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Services;

use App\Managers\PaymentManager;
use Illuminate\Support\Facades\Log;
use ReflectionMethod;
use RuntimeException;

final class CompatibilityGuard
{
    public const TARGET_LECONFE_VERSION = '1.4.6';
    public const TARGET_PAYPAL_VERSION = '1.1.0';

    public function assertCompatible(): void
    {
        if (PHP_VERSION_ID < 80100) {
            throw new RuntimeException('Conference Discount Eligibility requires PHP 8.1 or newer.');
        }
        if (! class_exists(PaymentManager::class)) {
            throw new RuntimeException('Leconfe PaymentManager was not found.');
        }

        $method = new ReflectionMethod(PaymentManager::class, 'queue');
        if ($method->isFinal()) {
            throw new RuntimeException('PaymentManager::queue() is final and cannot be extended safely.');
        }

        $expected = ['model','paymentFee','user','type','title','requestUrl','description','amount','currency','expiredAt','additionalItems','baseAmount'];
        $actual = array_map(static fn (\ReflectionParameter $parameter): string => $parameter->getName(), $method->getParameters());
        if ($actual !== $expected) {
            throw new RuntimeException('Unsupported PaymentManager::queue() signature; this package targets Leconfe 1.4.6.');
        }

        $version = $this->detectLeconfeVersion();
        if ($version !== null && $version !== self::TARGET_LECONFE_VERSION) {
            throw new RuntimeException(sprintf('Plugin 1.0.3 supports Leconfe %s; detected %s.', self::TARGET_LECONFE_VERSION, $version));
        }
        if ($version === null) {
            Log::warning('Conference Discount Eligibility could not read the Leconfe version file; the PaymentManager signature guard passed.');
        }
    }

    private function detectLeconfeVersion(): ?string
    {
        foreach ([base_path('version'), base_path('VERSION')] as $path) {
            if (! is_readable($path)) { continue; }
            $contents = trim((string) file_get_contents($path));
            if (preg_match('/\b(\d+\.\d+\.\d+)\b/', $contents, $matches)) { return $matches[1]; }
        }
        $configured = config('app.version');
        return is_string($configured) && preg_match('/\b(\d+\.\d+\.\d+)\b/', $configured, $matches) ? $matches[1] : null;
    }
}
