<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Panel\ScheduledConference\Resources\CouponCampaignResource\Pages;

use ConferenceDiscountEligibility\Panel\ScheduledConference\Resources\CouponCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListCouponCampaigns extends ListRecords
{
    protected static string $resource = CouponCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
