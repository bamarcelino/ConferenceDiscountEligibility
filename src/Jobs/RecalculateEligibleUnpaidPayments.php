<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Jobs;

use ConferenceDiscountEligibility\Services\RecalculationCoordinator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class RecalculateEligibleUnpaidPayments implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    public function __construct(
        public readonly string $ruleModel,
        public readonly int $ruleId,
        public readonly bool $notify = false,
    ) {}

    public function handle(RecalculationCoordinator $coordinator): void
    {
        $coordinator->run($this->ruleModel, $this->ruleId, $this->notify);
    }
}
