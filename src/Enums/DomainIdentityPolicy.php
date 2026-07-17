<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Enums;

enum DomainIdentityPolicy: string
{
    case VerifiedEmailOnly = 'verified_email_only';
    case VerifiedEmailOrConfirmedAuthor = 'verified_email_or_confirmed_author';

    public function accepts(bool $emailVerified, bool $confirmedAuthor): bool
    {
        if ($emailVerified) {
            return true;
        }

        return $this === self::VerifiedEmailOrConfirmedAuthor && $confirmedAuthor;
    }

    public function rejectionReason(bool $emailVerified, bool $confirmedAuthor): ?string
    {
        if ($this->accepts($emailVerified, $confirmedAuthor)) {
            return null;
        }

        return $this === self::VerifiedEmailOnly
            ? 'email_not_verified'
            : 'email_not_verified_and_not_confirmed_author';
    }
}
