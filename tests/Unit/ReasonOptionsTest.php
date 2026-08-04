<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Tests\Unit;

use ConferenceDiscountEligibility\Support\ReasonOptions;
use PHPUnit\Framework\TestCase;

final class ReasonOptionsTest extends TestCase
{
    public function testGenericReasonAndDetailsRoundTrip(): void
    {
        $reason = ReasonOptions::compose(ReasonOptions::FINANCIAL_HARDSHIP, null, 'Travel funding unavailable');

        self::assertSame('Financial hardship — Travel funding unavailable', $reason);
        self::assertSame([
            'code' => ReasonOptions::FINANCIAL_HARDSHIP,
            'custom' => null,
            'details' => 'Travel funding unavailable',
        ], ReasonOptions::parse($reason));
    }

    public function testOtherStoresAdministratorText(): void
    {
        self::assertSame(
            'Other: Special accessibility grant',
            ReasonOptions::compose(ReasonOptions::OTHER, 'Special accessibility grant', null),
        );
    }

    public function testOtherAndDetailsRoundTrip(): void
    {
        $reason = ReasonOptions::compose(ReasonOptions::OTHER, 'Special accessibility grant', 'Committee approval 42');

        self::assertSame([
            'code' => ReasonOptions::OTHER,
            'custom' => 'Special accessibility grant',
            'details' => 'Committee approval 42',
        ], ReasonOptions::parse($reason));
    }

    /** @dataProvider legacyReasons */
    public function testLegacyReasonsAreMappedWithoutDroppingOriginalText(string $legacy, string $code): void
    {
        $parsed = ReasonOptions::parse($legacy);

        self::assertSame($code, $parsed['code']);
        self::assertSame($legacy, $parsed['details']);
        self::assertStringContainsString($legacy, ReasonOptions::compose(...array_values($parsed)));
    }

    /** @return array<string, array{string, string}> */
    public static function legacyReasons(): array
    {
        return [
            'CLAEC member' => ['CLAEC active member', ReasonOptions::ACTIVE_MEMBER],
            'partner affiliate' => ['Institutional partner affiliate', ReasonOptions::INSTITUTIONAL_PARTNER],
            'Research4Life A' => ['Research4Life Group A', ReasonOptions::RESEARCH_SUPPORT_PROGRAM],
            'Research4Life B' => ['Research4Life Group B', ReasonOptions::RESEARCH_SUPPORT_PROGRAM],
        ];
    }
}
