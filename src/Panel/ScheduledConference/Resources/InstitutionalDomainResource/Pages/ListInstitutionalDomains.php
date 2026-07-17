<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Panel\ScheduledConference\Resources\InstitutionalDomainResource\Pages;

use ConferenceDiscountEligibility\Panel\ScheduledConference\Resources\InstitutionalDomainResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListInstitutionalDomains extends ListRecords
{
    protected static string $resource = InstitutionalDomainResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
