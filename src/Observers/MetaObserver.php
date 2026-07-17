<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Observers;

use App\Models\Meta;
use App\Models\Payment;
use ConferenceDiscountEligibility\Services\UnpaidPaymentRecalculator;
use Illuminate\Support\Facades\Log;
use Throwable;

final class MetaObserver
{
    private static bool $handling = false;

    public function __construct(private readonly UnpaidPaymentRecalculator $recalculator) {}

    public function saved(Meta $meta): void
    {
        if (self::$handling || (string) $meta->key !== 'base_amount') { return; }
        $metable = $meta->metable;
        if (! $metable instanceof Payment) { return; }

        self::$handling = true;
        try {
            $this->recalculator->reapplySnapshot($metable);
        } catch (Throwable $exception) {
            Log::error('Failed to reapply conference discount after native payment edit.', [
                'payment_id' => $metable->getKey(),
                'exception' => $exception,
            ]);
            throw $exception;
        } finally {
            self::$handling = false;
        }
    }
}
