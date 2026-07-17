<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Tests\Unit;

use ConferenceDiscountEligibility\Data\EligibilityCandidate;
use ConferenceDiscountEligibility\Enums\EligibilityType;
use ConferenceDiscountEligibility\Services\EligibilitySelector;
use PHPUnit\Framework\TestCase;

final class EligibilitySelectorTest extends TestCase
{
    public function testHighestPercentageWinsAndUserBreaksTie(): void
    {
        $selector = new EligibilitySelector();
        $selection = $selector->select([
            new EligibilityCandidate(EligibilityType::Domain, 1, 4000, 'Domain', true),
            new EligibilityCandidate(EligibilityType::User, 2, 4000, 'User', true),
        ]);
        self::assertSame(EligibilityType::User, $selection->winner?->type);
    }
}
