<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Panel\ScheduledConference\Resources\EmailEntitlementResource\Pages;

use App\Models\User;
use ConferenceDiscountEligibility\Enums\EligibilityType;
use ConferenceDiscountEligibility\Panel\ScheduledConference\Resources\EmailEntitlementResource;
use ConferenceDiscountEligibility\Services\EmailEntitlementLinker;
use ConferenceDiscountEligibility\Services\RecalculationCoordinator;
use ConferenceDiscountEligibility\Support\RecalculationFeedback;
use Filament\Resources\Pages\CreateRecord;

final class CreateEmailEntitlement extends CreateRecord
{
    protected static string $resource = EmailEntitlementResource::class;
    private bool $recalculate = false;
    private bool $notify = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->recalculate = (bool) ($data['recalculate_unpaid_payments'] ?? false);
        $this->notify = (bool) ($data['notify_on_recalculation'] ?? false);
        unset($data['recalculate_unpaid_payments'], $data['notify_on_recalculation']);
        $data['scheduled_conference_id'] = app()->getCurrentScheduledConference()->getKey();
        $data['eligibility_type'] = EligibilityType::Email->value;
        $data['source_type'] = 'manual';
        return $data;
    }

    protected function afterCreate(): void
    {
        $user = User::query()->whereRaw('LOWER(TRIM(email)) = ?', [$this->record->normalized_email])->first();
        if ($user) { app(EmailEntitlementLinker::class)->link($user, (int) $this->record->scheduled_conference_id); $this->record->refresh(); }
        if ($this->recalculate && $this->record->user_id) {
            $stats = app(RecalculationCoordinator::class)->run('entitlement', (int) $this->record->getKey(), $this->notify);
            RecalculationFeedback::send($stats);
        }
    }
}
