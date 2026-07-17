<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Tests\Unit;

use ConferenceDiscountEligibility\Support\CouponCode;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CouponCodeTest extends TestCase
{
    public function testNormalizeIsCaseInsensitiveAndRemovesWhitespace(): void
    {
        self::assertSame('PARTNER-40', CouponCode::normalize(" partner-40 \n"));
    }

    public function testHashIsKeyedAndDeterministic(): void
    {
        self::assertSame(
            CouponCode::hash('partner-40', 'test-key'),
            CouponCode::hash('PARTNER-40', 'test-key'),
        );
        self::assertNotSame(
            CouponCode::hash('PARTNER-40', 'test-key'),
            CouponCode::hash('PARTNER-40', 'different-key'),
        );
    }

    public function testGeneratedCodeHasHighEntropyAndValidFormat(): void
    {
        $first = CouponCode::generate('CDE');
        $second = CouponCode::generate('CDE');

        self::assertNotSame($first, $second);
        self::assertSame($first, CouponCode::normalize($first));
        self::assertMatchesRegularExpression('/^CDE-[A-F0-9]{8}(?:-[A-F0-9]{8}){3}$/D', $first);
    }

    public function testUnsafeOrTooShortCodeIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CouponCode::normalize('<x>');
    }

    public function testHintDoesNotExposeTheFullCode(): void
    {
        $code = 'CDE-12345678-90ABCDEF-12345678-90ABCDEF';
        $hint = CouponCode::hint($code);

        self::assertNotSame($code, $hint);
        self::assertStringStartsWith('CDE-12', $hint);
        self::assertStringEndsWith('CDEF', $hint);
    }
}
