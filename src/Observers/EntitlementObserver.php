<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Observers;

use ConferenceDiscountEligibility\Models\ConferenceDiscountEntitlement;
use ConferenceDiscountEligibility\Services\AuditLogger;

final class EntitlementObserver
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function creating(ConferenceDiscountEntitlement $record): void
    {
        $record->created_by ??= auth()->id();
        $record->updated_by ??= auth()->id();
    }

    public function updating(ConferenceDiscountEntitlement $record): void
    {
        $record->updated_by = auth()->id();
    }

    public function created(ConferenceDiscountEntitlement $record): void
    {
        $this->auditLogger->log('entitlement_created', (int) $record->scheduled_conference_id, $record, $record->user_id, newValues: $record->toArray());
    }

    public function updated(ConferenceDiscountEntitlement $record): void
    {
        $changes = $record->getChanges();
        $action = array_key_exists('active', $changes) && $record->active === false ? 'entitlement_deactivated' : 'entitlement_updated';
        $this->auditLogger->log($action, (int) $record->scheduled_conference_id, $record, $record->user_id, oldValues: array_intersect_key($record->getOriginal(), $changes), newValues: $changes);
    }

    public function deleted(ConferenceDiscountEntitlement $record): void
    {
        $this->auditLogger->log('entitlement_deleted', (int) $record->scheduled_conference_id, $record, $record->user_id, oldValues: $record->toArray());
    }
}
