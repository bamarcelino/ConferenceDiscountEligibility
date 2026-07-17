<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Tests\Unit;

use ConferenceDiscountEligibility\Support\DomainMatcher;
use PHPUnit\Framework\TestCase;

final class DomainMatcherTest extends TestCase
{
    public function testExactAndSubdomainBoundaries(): void
    {
        self::assertTrue(DomainMatcher::matches('universidade.edu', 'universidade.edu', false));
        self::assertTrue(DomainMatcher::matches('dept.universidade.edu', 'universidade.edu', true));
        self::assertFalse(DomainMatcher::matches('dept.universidade.edu', 'universidade.edu', false));
        self::assertFalse(DomainMatcher::matches('fakeuniversidade.edu', 'universidade.edu', true));
        self::assertFalse(DomainMatcher::matches('universidade.edu.example.com', 'universidade.edu', true));
    }
}
