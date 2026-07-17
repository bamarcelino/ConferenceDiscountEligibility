<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Observers;

use ConferenceDiscountEligibility\Models\ConferenceDiscountDomain;
use ConferenceDiscountEligibility\Services\AuditLogger;

final class DomainObserver
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function creating(ConferenceDiscountDomain $record): void
    {
        $record->created_by ??= auth()->id();
        $record->updated_by ??= auth()->id();
    }

    public function updating(ConferenceDiscountDomain $record): void
    {
        $record->updated_by = auth()->id();
    }

    public function created(ConferenceDiscountDomain $record): void
    {
        $this->auditLogger->log('domain_rule_created', (int) $record->scheduled_conference_id, $record, newValues: $record->toArray());
    }

    public function updated(ConferenceDiscountDomain $record): void
    {
        $changes = $record->getChanges();
        $action = array_key_exists('active', $changes) && $record->active === false ? 'domain_rule_deactivated' : 'domain_rule_updated';
        $this->auditLogger->log($action, (int) $record->scheduled_conference_id, $record, oldValues: array_intersect_key($record->getOriginal(), $changes), newValues: $changes);
    }

    public function deleted(ConferenceDiscountDomain $record): void
    {
        $this->auditLogger->log('domain_rule_deleted', (int) $record->scheduled_conference_id, $record, oldValues: $record->toArray());
    }
}
