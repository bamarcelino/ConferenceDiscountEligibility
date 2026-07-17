<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Tests\Unit;

use ConferenceDiscountEligibility\Support\CsvSanitizer;
use PHPUnit\Framework\TestCase;

final class CsvSanitizerTest extends TestCase
{
    public function testFormulaLikeCellsAreNeutralized(): void
    {
        self::assertSame("'=HYPERLINK(\"x\")", CsvSanitizer::safeCell('=HYPERLINK("x")'));
        self::assertSame('normal', CsvSanitizer::safeCell('normal'));
    }
}
