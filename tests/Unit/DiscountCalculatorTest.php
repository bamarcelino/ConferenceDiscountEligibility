<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Tests\Unit;

use ConferenceDiscountEligibility\Enums\DiscountScope;
use ConferenceDiscountEligibility\Services\DiscountCalculator;
use PHPUnit\Framework\TestCase;

final class DiscountCalculatorTest extends TestCase
{
    public function testBaseOnlyAndEligibleAddOns(): void
    {
        $calculator = new DiscountCalculator();
        $items = [['key' => 'addon_dinner', 'amount' => 5, 'quantity' => 1]];
        $baseOnly = $calculator->calculate(3500, $items, 4000, DiscountScope::BaseRegistrationFeeOnly, [], 'EUR', 4000);
        self::assertSame(2600, $baseOnly->finalTotalMinor);
        $withAddOn = $calculator->calculate(3500, $items, 4000, DiscountScope::BaseFeeAndEligibleAddOns, ['addon_dinner'], 'EUR', 4000);
        self::assertSame(2400, $withAddOn->finalTotalMinor);
    }
}
