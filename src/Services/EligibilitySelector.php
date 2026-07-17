<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Services;

use ConferenceDiscountEligibility\Data\EligibilityCandidate;
use ConferenceDiscountEligibility\Data\EligibilitySelection;

final class EligibilitySelector
{
    /** @param list<EligibilityCandidate> $candidates */
    public function select(array $candidates): EligibilitySelection
    {
        $eligible = array_values(array_filter($candidates, static fn (EligibilityCandidate $candidate): bool => $candidate->eligible && $candidate->percentageBasisPoints > 0));
        usort($eligible, static function (EligibilityCandidate $left, EligibilityCandidate $right): int {
            $byPercentage = $right->percentageBasisPoints <=> $left->percentageBasisPoints;
            return $byPercentage !== 0 ? $byPercentage : $right->type->priority() <=> $left->type->priority();
        });
        return new EligibilitySelection($eligible[0] ?? null, $candidates);
    }
}
