<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Tests\Unit;

use ConferenceDiscountEligibility\Support\AuthorEvidencePolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AuthorEvidencePolicyTest extends TestCase
{
    #[DataProvider('acceptedStatuses')]
    public function testAcceptedStatuses(string $status): void
    {
        self::assertTrue(AuthorEvidencePolicy::acceptsSubmissionStatus($status));
    }

    #[DataProvider('rejectedStatuses')]
    public function testRejectedStatuses(string $status): void
    {
        self::assertFalse(AuthorEvidencePolicy::acceptsSubmissionStatus($status));
    }

    /** @return iterable<string, array{string}> */
    public static function acceptedStatuses(): iterable
    {
        foreach (['Queued', 'On Review', 'On Payment', 'On Presentation', 'Editing', 'Published'] as $status) {
            yield $status => [$status];
        }
    }

    /** @return iterable<string, array{string}> */
    public static function rejectedStatuses(): iterable
    {
        foreach (['Incomplete', 'Payment Declined', 'Declined', 'Withdrawn'] as $status) {
            yield $status => [$status];
        }
    }
}
