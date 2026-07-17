<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Panel\ScheduledConference\Resources\IndividualEntitlementResource\Pages;

use ConferenceDiscountEligibility\Enums\EligibilityType;
use ConferenceDiscountEligibility\Jobs\RecalculateEligibleUnpaidPayments;
use ConferenceDiscountEligibility\Panel\ScheduledConference\Resources\IndividualEntitlementResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateIndividualEntitlement extends CreateRecord
{
    protected static string $resource = IndividualEntitlementResource::class;
    private bool $recalculate = false;
    private bool $notify = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->recalculate = (bool) ($data['recalculate_unpaid_payments'] ?? false);
        $this->notify = (bool) ($data['notify_on_recalculation'] ?? false);
        unset($data['recalculate_unpaid_payments'], $data['notify_on_recalculation']);
        $data['scheduled_conference_id'] = app()->getCurrentScheduledConference()->getKey();
        $data['eligibility_type'] = EligibilityType::User->value;
        $data['source_type'] = 'manual';
        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->recalculate) { RecalculateEligibleUnpaidPayments::dispatchSync('entitlement', (int) $this->record->getKey(), $this->notify); }
    }
}
