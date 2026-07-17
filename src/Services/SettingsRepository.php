<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Services;

use ConferenceDiscountEligibility\Database\SchemaDefinition;
use ConferenceDiscountEligibility\Enums\DiscountScope;
use ConferenceDiscountEligibility\Models\ConferenceDiscountSetting;

final class SettingsRepository
{
    public function forConference(int $scheduledConferenceId): ConferenceDiscountSetting
    {
        return ConferenceDiscountSetting::query()->firstOrCreate(
            ['scheduled_conference_id' => $scheduledConferenceId],
            [
                'discount_scope' => DiscountScope::BaseRegistrationFeeOnly->value,
                'eligible_add_on_keys' => [],
                'recalculate_unpaid_default' => false,
                'notify_on_recalculation' => false,
                'csv_max_bytes' => 5 * 1024 * 1024,
                'schema_version' => SchemaDefinition::VERSION,
            ],
        );
    }

    public function scope(int $scheduledConferenceId): DiscountScope
    {
        return $this->forConference($scheduledConferenceId)->scopeValue();
    }

    /** @return list<string> */
    public function eligibleAddOnKeys(int $scheduledConferenceId): array
    {
        $keys = $this->forConference($scheduledConferenceId)->eligible_add_on_keys;
        return array_values(array_unique(array_filter(array_map('strval', is_array($keys) ? $keys : []))));
    }
}
