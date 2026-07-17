<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Models;

use App\Models\ScheduledConference;
use ConferenceDiscountEligibility\Enums\DiscountScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ConferenceDiscountSetting extends Model
{
    protected $table = 'conference_discount_settings';

    protected $fillable = [
        'scheduled_conference_id',
        'discount_scope',
        'eligible_add_on_keys',
        'recalculate_unpaid_default',
        'notify_on_recalculation',
        'csv_max_bytes',
        'schema_version',
    ];

    protected $casts = [
        'eligible_add_on_keys' => 'array',
        'recalculate_unpaid_default' => 'boolean',
        'notify_on_recalculation' => 'boolean',
        'csv_max_bytes' => 'integer',
        'schema_version' => 'integer',
    ];

    public function scheduledConference(): BelongsTo
    {
        return $this->belongsTo(ScheduledConference::class);
    }

    public function scopeValue(): DiscountScope
    {
        return DiscountScope::tryFrom((string) $this->discount_scope)
            ?? DiscountScope::BaseRegistrationFeeOnly;
    }
}
