<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Services;

use ConferenceDiscountEligibility\Models\ConferenceDiscountAuditLog;
use Illuminate\Database\Eloquent\Model;
use Throwable;

final class AuditLogger
{
    /**
     * @param array<string, mixed>|null $oldValues
     * @param array<string, mixed>|null $newValues
     * @param array<string, mixed>|null $context
     */
    public function log(
        string $action,
        int $scheduledConferenceId,
        ?Model $auditable = null,
        ?int $affectedUserId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $context = null,
        string $origin = 'panel',
        ?int $actorUserId = null,
    ): ConferenceDiscountAuditLog {
        return ConferenceDiscountAuditLog::query()->create([
            'scheduled_conference_id' => $scheduledConferenceId,
            'actor_user_id' => $actorUserId ?? auth()->id(),
            'affected_user_id' => $affectedUserId,
            'action' => $action,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'old_values' => $this->redact($oldValues),
            'new_values' => $this->redact($newValues),
            'context' => $this->redact($context),
            'ip_hash' => $this->ipHash(),
            'origin' => $origin,
            'created_at' => now(),
        ]);
    }

    private function ipHash(): ?string
    {
        try {
            if (! app()->bound('request') || ! request()->ip()) { return null; }
            return hash_hmac('sha256', (string) request()->ip(), (string) config('app.key'));
        } catch (Throwable) {
            return null;
        }
    }

    private function redact(mixed $value, ?string $key = null): mixed
    {
        if ($key !== null && preg_match('/password|secret|token|client[_-]?id|credential|card/i', $key)) {
            return '[REDACTED]';
        }
        if (! is_array($value)) { return $value; }
        $result = [];
        foreach ($value as $itemKey => $itemValue) {
            $result[$itemKey] = $this->redact($itemValue, is_string($itemKey) ? $itemKey : null);
        }
        return $result;
    }
}
