<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Panel\ScheduledConference\Resources\CouponCampaignResource\Pages;

use ConferenceDiscountEligibility\Panel\ScheduledConference\Resources\CouponCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditCouponCampaign extends EditRecord
{
    protected static string $resource = CouponCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()->visible(fn (): bool => (int) $this->record->uses_count === 0)];
    }
}
