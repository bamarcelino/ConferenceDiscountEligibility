<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Observers;

use ConferenceDiscountEligibility\Models\ConferenceDiscountCoupon;
use ConferenceDiscountEligibility\Services\AuditLogger;

final class CouponObserver
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function creating(ConferenceDiscountCoupon $record): void
    {
        $record->created_by ??= auth()->id();
        $record->updated_by ??= auth()->id();
    }

    public function updating(ConferenceDiscountCoupon $record): void
    {
        $record->updated_by = auth()->id();
    }

    public function created(ConferenceDiscountCoupon $record): void
    {
        $this->auditLogger->log(
            'coupon_campaign_created',
            (int) $record->scheduled_conference_id,
            $record,
            newValues: $this->safeValues($record),
        );
    }

    public function updated(ConferenceDiscountCoupon $record): void
    {
        $changes = $record->getChanges();
        unset($changes['code_hash']);
        $action = array_key_exists('active', $changes) && $record->active === false
            ? 'coupon_campaign_deactivated'
            : 'coupon_campaign_updated';
        $original = array_intersect_key($record->getOriginal(), $changes);
        unset($original['code_hash']);

        $this->auditLogger->log(
            $action,
            (int) $record->scheduled_conference_id,
            $record,
            oldValues: $original,
            newValues: $changes,
        );
    }

    public function deleted(ConferenceDiscountCoupon $record): void
    {
        $this->auditLogger->log(
            'coupon_campaign_deleted',
            (int) $record->scheduled_conference_id,
            $record,
            oldValues: $this->safeValues($record),
        );
    }

    /** @return array<string, mixed> */
    private function safeValues(ConferenceDiscountCoupon $record): array
    {
        $values = $record->toArray();
        unset($values['code_hash']);

        return $values;
    }
}
