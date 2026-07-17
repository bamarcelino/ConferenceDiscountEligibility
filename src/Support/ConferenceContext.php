<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Support;

use App\Models\ScheduledConference;
use RuntimeException;

final class ConferenceContext
{
    public static function current(): ScheduledConference
    {
        $conference = app()->getCurrentScheduledConference();
        if (! $conference instanceof ScheduledConference) { throw new RuntimeException('No scheduled conference is active.'); }
        return $conference;
    }

    public static function id(): int
    {
        return (int) self::current()->getKey();
    }
}
