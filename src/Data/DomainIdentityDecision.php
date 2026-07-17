<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Data;

use ConferenceDiscountEligibility\Enums\DomainIdentityPolicy;

final readonly class DomainIdentityDecision
{
    public function __construct(
        public bool $eligible,
        public DomainIdentityPolicy $policy,
        public bool $emailVerified,
        public AuthorIdentityEvidence $authorEvidence,
        public ?string $rejectionReason = null,
    ) {}

    public function usedAuthorEvidence(): bool
    {
        return $this->eligible
            && ! $this->emailVerified
            && $this->authorEvidence->confirmed;
    }

    public function verificationMethod(): ?string
    {
        if (! $this->eligible) {
            return null;
        }

        return $this->emailVerified ? 'verified_email' : 'confirmed_author';
    }

    /** @return array<string, bool|int|string|null> */
    public function toArray(): array
    {
        return [
            'email_verified' => $this->emailVerified,
            'identity_policy' => $this->policy->value,
            'identity_accepted' => $this->eligible,
            'identity_verification_method' => $this->verificationMethod(),
            'identity_rejection_reason' => $this->rejectionReason,
            ...$this->authorEvidence->toArray(),
        ];
    }
}
