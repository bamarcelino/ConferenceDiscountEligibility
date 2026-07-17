<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Panel\ScheduledConference\Resources\IndividualEntitlementResource\Pages;

use ConferenceDiscountEligibility\Panel\ScheduledConference\Resources\IndividualEntitlementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListIndividualEntitlements extends ListRecords
{
    protected static string $resource = IndividualEntitlementResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
