<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Services;

use App\Models\User;
use ConferenceDiscountEligibility\Data\AuthorIdentityEvidence;
use ConferenceDiscountEligibility\Data\DomainIdentityDecision;
use ConferenceDiscountEligibility\Enums\DomainIdentityPolicy;

final class DomainIdentityVerifier
{
    public function __construct(private readonly AuthorIdentityVerifier $authorIdentityVerifier) {}

    public function evaluate(
        DomainIdentityPolicy $policy,
        int $scheduledConferenceId,
        ?User $user,
        ?string $email,
    ): DomainIdentityDecision {
        $emailVerified = $user?->hasVerifiedEmail() ?? false;
        $authorEvidence = AuthorIdentityEvidence::none();

        if (
            ! $emailVerified
            && $user !== null
            && $policy === DomainIdentityPolicy::VerifiedEmailOrConfirmedAuthor
        ) {
            $authorEvidence = $this->authorIdentityVerifier->inspect(
                $scheduledConferenceId,
                $user,
                $email,
            );
        }

        $eligible = $policy->accepts($emailVerified, $authorEvidence->confirmed);

        return new DomainIdentityDecision(
            eligible: $eligible,
            policy: $policy,
            emailVerified: $emailVerified,
            authorEvidence: $authorEvidence,
            rejectionReason: $policy->rejectionReason(
                $emailVerified,
                $authorEvidence->confirmed,
            ),
        );
    }
}
