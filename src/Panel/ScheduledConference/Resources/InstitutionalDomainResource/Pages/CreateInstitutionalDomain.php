<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Panel\ScheduledConference\Resources\InstitutionalDomainResource\Pages;

use ConferenceDiscountEligibility\Panel\ScheduledConference\Resources\InstitutionalDomainResource;
use ConferenceDiscountEligibility\Services\RecalculationCoordinator;
use ConferenceDiscountEligibility\Support\RecalculationFeedback;
use Filament\Resources\Pages\CreateRecord;

final class CreateInstitutionalDomain extends CreateRecord
{
    protected static string $resource = InstitutionalDomainResource::class;
    private bool $recalculate = false;
    private bool $notify = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->recalculate = (bool) ($data['recalculate_unpaid_payments'] ?? false);
        $this->notify = (bool) ($data['notify_on_recalculation'] ?? false);
        unset($data['recalculate_unpaid_payments'], $data['notify_on_recalculation']);
        $data['scheduled_conference_id'] = app()->getCurrentScheduledConference()->getKey();
        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->recalculate) {
            $stats = app(RecalculationCoordinator::class)->run('domain', (int) $this->record->getKey(), $this->notify);
            RecalculationFeedback::send($stats);
        }
    }
}
