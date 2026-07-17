<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Models;

use App\Models\ScheduledConference;
use App\Models\User;
use ConferenceDiscountEligibility\Enums\DomainIdentityPolicy;
use ConferenceDiscountEligibility\Support\DomainMatcher;
use ConferenceDiscountEligibility\Support\Percentage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

final class ConferenceDiscountDomain extends Model
{
    protected $table = 'conference_discount_domains';

    protected $fillable = [
        'scheduled_conference_id',
        'original_domain',
        'normalized_domain',
        'percentage_basis_points',
        'percentage',
        'reason',
        'notes',
        'include_subdomains',
        'identity_policy',
        'valid_from',
        'valid_until',
        'active',
        'maximum_uses',
        'uses_count',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'percentage_basis_points' => 'integer',
        'include_subdomains' => 'boolean',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'active' => 'boolean',
        'maximum_uses' => 'integer',
        'uses_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(static function (self $model): void {
            Percentage::assertBasisPoints((int) $model->percentage_basis_points);
            if (
                $model->valid_from !== null
                && $model->valid_until !== null
                && $model->valid_until->lt($model->valid_from)
            ) {
                throw new InvalidArgumentException('Validity end must be after validity start.');
            }
            if ($model->maximum_uses !== null && (int) $model->maximum_uses < 1) {
                throw new InvalidArgumentException('Maximum uses must be at least one.');
            }

            $model->original_domain = trim((string) $model->original_domain);
            $model->normalized_domain = DomainMatcher::normalize($model->original_domain);
            $model->identity_policy = (DomainIdentityPolicy::tryFrom((string) $model->identity_policy)
                ?? DomainIdentityPolicy::VerifiedEmailOnly)->value;
            if ($model->normalized_domain === null) {
                throw new InvalidArgumentException('A valid institutional domain is required.');
            }
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

    public function identityPolicy(): DomainIdentityPolicy
    {
        return DomainIdentityPolicy::tryFrom((string) $this->identity_policy)
            ?? DomainIdentityPolicy::VerifiedEmailOnly;
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
}
