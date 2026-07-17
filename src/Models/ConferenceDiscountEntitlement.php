<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Models;

use App\Models\ScheduledConference;
use App\Models\User;
use ConferenceDiscountEligibility\Enums\EligibilityType;
use ConferenceDiscountEligibility\Support\EmailNormalizer;
use ConferenceDiscountEligibility\Support\Percentage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

final class ConferenceDiscountEntitlement extends Model
{
    protected $table = 'conference_discount_entitlements';

    protected $fillable = [
        'scheduled_conference_id',
        'eligibility_type',
        'user_id',
        'original_email',
        'normalized_email',
        'percentage_basis_points',
        'percentage',
        'reason',
        'notes',
        'valid_from',
        'valid_until',
        'active',
        'source_type',
        'source_reference',
        'maximum_uses',
        'uses_count',
        'linked_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'percentage_basis_points' => 'integer',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'active' => 'boolean',
        'maximum_uses' => 'integer',
        'uses_count' => 'integer',
        'linked_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(static function (self $model): void {
            $type = EligibilityType::tryFrom((string) $model->eligibility_type);
            if (! in_array($type, [EligibilityType::User, EligibilityType::Email], true)) {
                throw new InvalidArgumentException('Entitlement type must be user or email.');
            }

            Percentage::assertBasisPoints((int) $model->percentage_basis_points);
            if ($model->valid_from !== null && $model->valid_until !== null && $model->valid_until->lt($model->valid_from)) {
                throw new InvalidArgumentException('Validity end must be after validity start.');
            }
            if ($model->maximum_uses !== null && (int) $model->maximum_uses < 1) {
                throw new InvalidArgumentException('Maximum uses must be at least one.');
            }

            if ($type === EligibilityType::User) {
                if (! $model->user_id) {
                    throw new InvalidArgumentException('A direct user entitlement requires a user.');
                }
                $model->original_email = null;
                $model->normalized_email = null;
            } else {
                $model->original_email = trim((string) $model->original_email);
                $model->normalized_email = EmailNormalizer::normalize($model->original_email);
                if ($model->normalized_email === null) {
                    throw new InvalidArgumentException('A valid email address is required.');
                }
            }

            $model->source_type = $model->source_type ?: 'manual';
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

    public function type(): EligibilityType
    {
        return EligibilityType::from((string) $this->eligibility_type);
    }

    public function scheduledConference(): BelongsTo
    {
        return $this->belongsTo(ScheduledConference::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
