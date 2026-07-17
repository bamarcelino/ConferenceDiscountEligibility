<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Data;

use ConferenceDiscountEligibility\Enums\EligibilityType;

final class EligibilityCandidate
{
    /** @param array<string, mixed> $context */
    public function __construct(
        public EligibilityType $type,
        public int $id,
        public int $percentageBasisPoints,
        public string $reason,
        public bool $eligible,
        public ?string $rejectionReason = null,
        public array $context = [],
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'id' => $this->id,
            'percentage_basis_points' => $this->percentageBasisPoints,
            'reason' => $this->reason,
            'eligible' => $this->eligible,
            'rejection_reason' => $this->rejectionReason,
            'context' => $this->context,
        ];
    }
}
