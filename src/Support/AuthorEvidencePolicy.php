<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Support;

use BackedEnum;

final class AuthorEvidencePolicy
{
    /** @return list<string> */
    public static function acceptedSubmissionStatuses(): array
    {
        return [
            'Queued',
            'On Review',
            'On Payment',
            'On Presentation',
            'Editing',
            'Published',
        ];
    }

    public static function acceptsSubmissionStatus(BackedEnum|string|null $status): bool
    {
        $value = $status instanceof BackedEnum ? (string) $status->value : $status;

        return is_string($value)
            && in_array($value, self::acceptedSubmissionStatuses(), true);
    }
}
