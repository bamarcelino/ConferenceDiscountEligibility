<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Panel\ScheduledConference\Resources\CouponCampaignResource\Pages;

use ConferenceDiscountEligibility\Panel\ScheduledConference\Resources\CouponCampaignResource;
use ConferenceDiscountEligibility\Support\CouponCode;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

final class CreateCouponCampaign extends CreateRecord
{
    protected static string $resource = CouponCampaignResource::class;

    private string $plainCode = '';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $mode = (string) ($data['code_mode'] ?? 'generate');
        $this->plainCode = $mode === 'manual'
            ? CouponCode::normalize((string) ($data['coupon_code'] ?? ''))
            : CouponCode::generate('CDE');

        unset($data['code_mode'], $data['coupon_code']);
        $data['scheduled_conference_id'] = app()->getCurrentScheduledConference()->getKey();
        $data['code_hash'] = CouponCode::hash($this->plainCode);
        $data['code_hint'] = CouponCode::hint($this->plainCode);

        return $data;
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->success()
            ->persistent()
            ->title(__('ConferenceDiscountEligibility::messages.coupon_code_generated'))
            ->body(__('ConferenceDiscountEligibility::messages.coupon_copy_now', ['code' => $this->plainCode]))
            ->send();
    }
}
