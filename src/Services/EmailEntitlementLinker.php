<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Services;

use App\Models\User;
use ConferenceDiscountEligibility\Enums\EligibilityType;
use ConferenceDiscountEligibility\Models\ConferenceDiscountEntitlement;
use ConferenceDiscountEligibility\Support\EmailNormalizer;
use Illuminate\Support\Facades\DB;

final class EmailEntitlementLinker
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function link(User $user, ?int $scheduledConferenceId = null): int
    {
        $email = EmailNormalizer::normalize($user->email);
        if ($email === null) { return 0; }

        return DB::transaction(function () use ($user, $scheduledConferenceId, $email): int {
            $query = ConferenceDiscountEntitlement::query()
                ->where('eligibility_type', EligibilityType::Email->value)
                ->where('normalized_email', $email)
                ->lockForUpdate();
            if ($scheduledConferenceId !== null) { $query->where('scheduled_conference_id', $scheduledConferenceId); }

            $linked = 0;
            foreach ($query->get() as $entitlement) {
                if ($entitlement->user_id !== null && (int) $entitlement->user_id !== (int) $user->getKey()) { continue; }
                if ((int) $entitlement->user_id === (int) $user->getKey()) { continue; }
                $entitlement->forceFill(['user_id' => $user->getKey(), 'linked_at' => now()])->save();
                $this->auditLogger->log(
                    action: 'email_entitlement_linked',
                    scheduledConferenceId: (int) $entitlement->scheduled_conference_id,
                    auditable: $entitlement,
                    affectedUserId: (int) $user->getKey(),
                    newValues: ['user_id' => $user->getKey(), 'normalized_email' => $email],
                    origin: 'user_observer',
                );
                $linked++;
            }
            return $linked;
        });
    }
}
