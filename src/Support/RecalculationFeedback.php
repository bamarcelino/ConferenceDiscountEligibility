<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Support;

use Filament\Notifications\Notification;

final class RecalculationFeedback
{
    /** @param array<string, int> $stats */
    public static function send(array $stats): void
    {
        $discounted = (int) ($stats['discounted'] ?? 0);
        $failed = (int) ($stats['failed'] ?? 0);
        $matched = (int) ($stats['matched'] ?? 0);
        $unverified = (int) ($stats['unverified_domain_matches'] ?? 0);
        $confirmedAuthors = (int) ($stats['confirmed_author_domain_matches'] ?? 0);

        $notification = Notification::make()
            ->title($discounted > 0
                ? __('ConferenceDiscountEligibility::messages.recalculation_applied')
                : __('ConferenceDiscountEligibility::messages.recalculation_no_change'))
            ->body(__('ConferenceDiscountEligibility::messages.recalculation_summary', [
                'matched' => $matched,
                'discounted' => $discounted,
                'unchanged' => (int) ($stats['unchanged'] ?? 0),
                'skipped' => (int) ($stats['skipped'] ?? 0),
                'paid' => (int) ($stats['paid'] ?? 0),
                'failed' => $failed,
                'unverified' => $unverified,
                'confirmed_authors' => $confirmedAuthors,
            ]));

        if ($failed > 0) {
            $notification->danger();
        } elseif ($discounted > 0) {
            $notification->success();
        } else {
            $notification->warning();
        }

        $notification->persistent()->send();
    }
}
