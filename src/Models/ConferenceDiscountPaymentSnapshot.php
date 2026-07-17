<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Models;

use App\Models\Payment;
use App\Models\ScheduledConference;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ConferenceDiscountPaymentSnapshot extends Model
{
    protected $table = 'conference_discount_payment_snapshots';

    protected $fillable = [
        'scheduled_conference_id',
        'payment_id',
        'user_id',
        'entitlement_id',
        'domain_rule_id',
        'original_base_amount_minor',
        'discount_percentage_basis_points',
        'base_discount_amount_minor',
        'discount_amount_minor',
        'final_base_amount_minor',
        'add_on_amount_minor',
        'eligible_add_on_amount_minor',
        'add_on_discount_amount_minor',
        'original_total_minor',
        'final_total_minor',
        'currency',
        'eligibility_type',
        'eligibility_id',
        'eligibility_reason',
        'eligibility_snapshot_at',
        'calculation_version',
        'metadata',
    ];

    protected $casts = [
        'original_base_amount_minor' => 'integer',
        'discount_percentage_basis_points' => 'integer',
        'base_discount_amount_minor' => 'integer',
        'discount_amount_minor' => 'integer',
        'final_base_amount_minor' => 'integer',
        'add_on_amount_minor' => 'integer',
        'eligible_add_on_amount_minor' => 'integer',
        'add_on_discount_amount_minor' => 'integer',
        'original_total_minor' => 'integer',
        'final_total_minor' => 'integer',
        'eligibility_snapshot_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function scheduledConference(): BelongsTo
    {
        return $this->belongsTo(ScheduledConference::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function entitlement(): BelongsTo
    {
        return $this->belongsTo(ConferenceDiscountEntitlement::class, 'entitlement_id');
    }

    public function domainRule(): BelongsTo
    {
        return $this->belongsTo(ConferenceDiscountDomain::class, 'domain_rule_id');
    }
}
