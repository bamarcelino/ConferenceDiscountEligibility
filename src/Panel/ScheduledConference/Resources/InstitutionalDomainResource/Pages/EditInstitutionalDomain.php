<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Panel\ScheduledConference\Resources\InstitutionalDomainResource\Pages;

use ConferenceDiscountEligibility\Panel\ScheduledConference\Resources\InstitutionalDomainResource;
use ConferenceDiscountEligibility\Services\RecalculationCoordinator;
use ConferenceDiscountEligibility\Support\RecalculationFeedback;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditInstitutionalDomain extends EditRecord
{
    protected static string $resource = InstitutionalDomainResource::class;

    private bool $recalculate = false;

    private bool $notify = false;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['recalculate_unpaid_payments'] = false;
        $data['notify_on_recalculation'] = false;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->recalculate = (bool) ($data['recalculate_unpaid_payments'] ?? false);
        $this->notify = (bool) ($data['notify_on_recalculation'] ?? false);
        unset($data['recalculate_unpaid_payments'], $data['notify_on_recalculation']);

        return $data;
    }

    protected function afterSave(): void
    {
        if (! $this->recalculate) {
            return;
        }

        $stats = app(RecalculationCoordinator::class)->run(
            'domain',
            (int) $this->record->getKey(),
            $this->notify,
        );
        RecalculationFeedback::send($stats);
    }
}
