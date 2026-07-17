<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Models;

use App\Models\Payment;
use App\Models\ScheduledConference;
use App\Models\User;
use ConferenceDiscountEligibility\Enums\CouponStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ConferenceDiscountCouponRedemption extends Model
{
    protected $table = 'conference_discount_coupon_redemptions';

    protected $fillable = [
        'scheduled_conference_id',
        'coupon_campaign_id',
        'payment_id',
        'user_id',
        'status',
        'reserved_at',
        'consumed_at',
        'released_at',
        'metadata',
    ];

    protected $casts = [
        'reserved_at' => 'datetime',
        'consumed_at' => 'datetime',
        'released_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function statusValue(): CouponStatus
    {
        return CouponStatus::tryFrom((string) $this->status) ?? CouponStatus::Released;
    }

    public function scheduledConference(): BelongsTo
    {
        return $this->belongsTo(ScheduledConference::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(ConferenceDiscountCoupon::class, 'coupon_campaign_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
