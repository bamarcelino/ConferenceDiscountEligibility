<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Models;

use App\Models\ScheduledConference;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ConferenceDiscountImportBatch extends Model
{
    protected $table = 'conference_discount_import_batches';

    protected $fillable = [
        'scheduled_conference_id',
        'actor_user_id',
        'original_filename',
        'sha256',
        'duplicate_strategy',
        'dry_run',
        'status',
        'total_rows',
        'accepted_rows',
        'rejected_rows',
        'duplicate_rows',
        'updated_rows',
        'ignored_rows',
        'report',
    ];

    protected $casts = [
        'dry_run' => 'boolean',
        'total_rows' => 'integer',
        'accepted_rows' => 'integer',
        'rejected_rows' => 'integer',
        'duplicate_rows' => 'integer',
        'updated_rows' => 'integer',
        'ignored_rows' => 'integer',
        'report' => 'array',
    ];

    public function scheduledConference(): BelongsTo
    {
        return $this->belongsTo(ScheduledConference::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
