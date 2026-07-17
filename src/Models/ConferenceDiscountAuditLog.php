<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Models;

use App\Models\ScheduledConference;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

final class ConferenceDiscountAuditLog extends Model
{
    public $timestamps = false;

    protected $table = 'conference_discount_audit_logs';

    protected $fillable = [
        'scheduled_conference_id',
        'actor_user_id',
        'affected_user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'context',
        'ip_hash',
        'origin',
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'context' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(static fn (): never => throw new LogicException('Audit log records are append-only.'));
        static::deleting(static fn (): never => throw new LogicException('Audit log records are append-only.'));
    }

    public function scheduledConference(): BelongsTo
    {
        return $this->belongsTo(ScheduledConference::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function affectedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'affected_user_id');
    }
}
