<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Tests\Unit;

use ConferenceDiscountEligibility\Data\AuthorIdentityEvidence;
use ConferenceDiscountEligibility\Data\DomainIdentityDecision;
use ConferenceDiscountEligibility\Enums\DomainIdentityPolicy;
use PHPUnit\Framework\TestCase;

final class DomainIdentityPolicyTest extends TestCase
{
    public function testVerifiedEmailOnlyRemainsTheDefaultSecurityBehavior(): void
    {
        $policy = DomainIdentityPolicy::VerifiedEmailOnly;

        self::assertTrue($policy->accepts(true, false));
        self::assertFalse($policy->accepts(false, true));
        self::assertSame('email_not_verified', $policy->rejectionReason(false, true));
    }

    public function testConfirmedAuthorFallbackMustBeExplicitlySelected(): void
    {
        $policy = DomainIdentityPolicy::VerifiedEmailOrConfirmedAuthor;

        self::assertTrue($policy->accepts(false, true));
        self::assertFalse($policy->accepts(false, false));
    }

    public function testDecisionSerializesAuditableEvidence(): void
    {
        $decision = new DomainIdentityDecision(
            eligible: true,
            policy: DomainIdentityPolicy::VerifiedEmailOrConfirmedAuthor,
            emailVerified: false,
            authorEvidence: new AuthorIdentityEvidence(
                confirmed: true,
                source: 'submission_owner',
                submissionId: 42,
                submissionStatus: 'Queued',
            ),
        );

        self::assertTrue($decision->usedAuthorEvidence());
        self::assertSame('confirmed_author', $decision->verificationMethod());
        self::assertSame(42, $decision->toArray()['author_evidence_submission_id']);
    }
}
