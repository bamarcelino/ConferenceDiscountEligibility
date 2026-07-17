<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Data;

final class EligibilitySelection
{
    /** @param list<EligibilityCandidate> $evaluated */
    public function __construct(public ?EligibilityCandidate $winner, public array $evaluated) {}

    public function hasDiscount(): bool
    {
        return $this->winner !== null && $this->winner->percentageBasisPoints > 0;
    }

    /** @return list<array<string, mixed>> */
    public function evaluatedAsArray(): array
    {
        return array_map(static fn (EligibilityCandidate $candidate): array => $candidate->toArray(), $this->evaluated);
    }
}
