<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Models;

use App\Models\PaymentFee;
use App\Models\ScheduledConference;
use App\Models\User;
use ConferenceDiscountEligibility\Support\CouponPaymentTypes;
use ConferenceDiscountEligibility\Support\Percentage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

final class ConferenceDiscountCoupon extends Model
{
    protected $table = 'conference_discount_coupons';

    protected $fillable = [
        'scheduled_conference_id',
        'name',
        'code_hash',
        'code_hint',
        'percentage_basis_points',
        'percentage',
        'reason',
        'notes',
        'eligible_payment_types',
        'eligible_payment_fee_ids',
        'valid_from',
        'valid_until',
        'active',
        'maximum_uses',
        'per_user_limit',
        'uses_count',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'percentage_basis_points' => 'integer',
        'eligible_payment_types' => 'array',
        'eligible_payment_fee_ids' => 'array',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'active' => 'boolean',
        'maximum_uses' => 'integer',
        'per_user_limit' => 'integer',
        'uses_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(static function (self $model): void {
            Percentage::assertBasisPoints((int) $model->percentage_basis_points);

            if ($model->valid_from !== null && $model->valid_until !== null && $model->valid_until->lt($model->valid_from)) {
                throw new InvalidArgumentException('Validity end must be after validity start.');
            }
            if ($model->maximum_uses !== null && (int) $model->maximum_uses < 1) {
                throw new InvalidArgumentException('Maximum uses must be at least one.');
            }
            if ((int) ($model->per_user_limit ?? 1) < 1) {
                throw new InvalidArgumentException('Per-user limit must be at least one.');
            }
            if (! preg_match('/^[a-f0-9]{64}$/D', (string) $model->code_hash)) {
                throw new InvalidArgumentException('Coupon code hash is invalid.');
            }

            $types = array_values(array_unique(array_filter(array_map('strval', $model->eligible_payment_types ?? []))));
            if ($types === [] || array_diff($types, CouponPaymentTypes::all()) !== []) {
                throw new InvalidArgumentException('At least one valid payment type is required.');
            }
            $model->eligible_payment_types = $types;

            $feeIds = array_values(array_unique(array_filter(array_map('intval', $model->eligible_payment_fee_ids ?? []), static fn (int $id): bool => $id > 0)));
            if ($feeIds !== []) {
                $validCount = PaymentFee::query()
                    ->where('scheduled_conference_id', $model->scheduled_conference_id)
                    ->whereIn('id', $feeIds)
                    ->count();
                if ($validCount !== count($feeIds)) {
                    throw new InvalidArgumentException('One or more selected payment fees do not belong to this scheduled conference.');
                }
            }
            $model->eligible_payment_fee_ids = $feeIds !== [] ? $feeIds : null;
            $model->per_user_limit = max(1, (int) ($model->per_user_limit ?? 1));
            $model->uses_count = max(0, (int) ($model->uses_count ?? 0));
        });
    }

    public function getPercentageAttribute(): float
    {
        return ((int) $this->percentage_basis_points) / 100;
    }

    public function setPercentageAttribute(int|float|string $value): void
    {
        $this->attributes['percentage_basis_points'] = Percentage::percentToBasisPoints($value);
    }

    public function scheduledConference(): BelongsTo
    {
        return $this->belongsTo(ScheduledConference::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(ConferenceDiscountCouponRedemption::class, 'coupon_campaign_id');
    }
}
