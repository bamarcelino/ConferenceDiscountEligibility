<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Panel\ScheduledConference\Resources\EmailEntitlementResource\Pages;

use ConferenceDiscountEligibility\Panel\ScheduledConference\Resources\EmailEntitlementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditEmailEntitlement extends EditRecord
{
    protected static string $resource = EmailEntitlementResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
    protected function mutateFormDataBeforeFill(array $data): array { $data['recalculate_unpaid_payments'] = false; $data['notify_on_recalculation'] = false; return $data; }
    protected function mutateFormDataBeforeSave(array $data): array { unset($data['recalculate_unpaid_payments'], $data['notify_on_recalculation']); return $data; }
}
