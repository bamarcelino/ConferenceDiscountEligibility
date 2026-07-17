<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Panel\ScheduledConference\Resources\EmailEntitlementResource\Pages;

use ConferenceDiscountEligibility\Panel\ScheduledConference\Resources\EmailEntitlementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListEmailEntitlements extends ListRecords
{
    protected static string $resource = EmailEntitlementResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
