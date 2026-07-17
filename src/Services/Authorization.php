<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Services;

final class Authorization
{
    public function canManage(): bool
    {
        $conference = app()->getCurrentScheduledConference();
        $user = auth()->user();

        return $conference !== null && $user !== null && $user->can('update', $conference);
    }

    public function authorizeManage(): void
    {
        abort_unless($this->canManage(), 403);
    }
}
