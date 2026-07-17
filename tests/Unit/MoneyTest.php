<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Tests\Unit;

use ConferenceDiscountEligibility\Support\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function testMinorUnitsAndRounding(): void
    {
        self::assertSame(3500, Money::toMinor('35.00', 'EUR'));
        self::assertSame(34, Money::toMinor('0.335', 'EUR'));
        self::assertSame('21.00', Money::decimal(2100, 'EUR'));
    }

    public function testNonTwoDecimalCurrencyFailsClosed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::toMinor('100', 'JPY');
    }
}
