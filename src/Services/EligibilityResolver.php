<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Services;

use App\Models\User;
use Carbon\CarbonInterface;
use ConferenceDiscountEligibility\Data\DomainIdentityDecision;
use ConferenceDiscountEligibility\Data\EligibilityCandidate;
use ConferenceDiscountEligibility\Data\EligibilitySelection;
use ConferenceDiscountEligibility\Enums\EligibilityType;
use ConferenceDiscountEligibility\Models\ConferenceDiscountDomain;
use ConferenceDiscountEligibility\Models\ConferenceDiscountEntitlement;
use ConferenceDiscountEligibility\Support\DomainMatcher;
use ConferenceDiscountEligibility\Support\EmailNormalizer;
use ConferenceDiscountEligibility\Support\RuleValidity;
use Illuminate\Database\Eloquent\Builder;

final class EligibilityResolver
{
    public function __construct(
        private readonly EligibilitySelector $selector,
        private readonly EmailEntitlementLinker $linker,
        private readonly DomainIdentityVerifier $domainIdentityVerifier,
    ) {}

    public function resolve(
        int $scheduledConferenceId,
        ?User $user,
        ?string $email,
        CarbonInterface $at,
        bool $lock = false,
    ): EligibilitySelection {
        if ($user !== null) {
            $this->linker->link($user, $scheduledConferenceId);
        }

        $normalizedEmail = EmailNormalizer::normalize($email ?? $user?->email);
        $candidates = [];

        if ($user !== null) {
            $query = ConferenceDiscountEntitlement::query()
                ->where('scheduled_conference_id', $scheduledConferenceId)
                ->where('eligibility_type', EligibilityType::User->value)
                ->where('user_id', $user->getKey());
            $this->applyLock($query, $lock);

            foreach ($query->get() as $rule) {
                $candidates[] = $this->entitlementCandidate($rule, EligibilityType::User, $at);
            }
        }

        if ($normalizedEmail !== null) {
            $query = ConferenceDiscountEntitlement::query()
                ->where('scheduled_conference_id', $scheduledConferenceId)
                ->where('eligibility_type', EligibilityType::Email->value)
                ->where('normalized_email', $normalizedEmail);
            $this->applyLock($query, $lock);

            foreach ($query->get() as $rule) {
                $candidates[] = $this->entitlementCandidate($rule, EligibilityType::Email, $at);
            }

            $domain = EmailNormalizer::domain($normalizedEmail);
            if ($domain !== null) {
                $query = ConferenceDiscountDomain::query()
                    ->where('scheduled_conference_id', $scheduledConferenceId)
                    ->whereIn('normalized_domain', DomainMatcher::suffixes($domain));
                $this->applyLock($query, $lock);

                /** @var array<string, DomainIdentityDecision> $identityDecisions */
                $identityDecisions = [];

                foreach ($query->get() as $rule) {
                    $policy = $rule->identityPolicy();
                    $decision = $identityDecisions[$policy->value]
                        ??= $this->domainIdentityVerifier->evaluate(
                            $policy,
                            $scheduledConferenceId,
                            $user,
                            $normalizedEmail,
                        );

                    $reason = RuleValidity::rejectionReason(
                        (bool) $rule->active,
                        $rule->valid_from,
                        $rule->valid_until,
                        $rule->maximum_uses,
                        (int) $rule->uses_count,
                        $at,
                    );

                    if (! DomainMatcher::matches(
                        $domain,
                        (string) $rule->normalized_domain,
                        (bool) $rule->include_subdomains,
                    )) {
                        $reason = 'domain_boundary_mismatch';
                    } elseif ($reason === null && ! $decision->eligible) {
                        $reason = $decision->rejectionReason;
                    }

                    $candidates[] = new EligibilityCandidate(
                        type: EligibilityType::Domain,
                        id: (int) $rule->getKey(),
                        percentageBasisPoints: (int) $rule->percentage_basis_points,
                        reason: (string) $rule->reason,
                        eligible: $reason === null,
                        rejectionReason: $reason,
                        context: [
                            'domain' => $domain,
                            'rule_domain' => $rule->normalized_domain,
                            'include_subdomains' => (bool) $rule->include_subdomains,
                            'identity_policy' => $policy->value,
                            ...$decision->toArray(),
                        ],
                    );
                }
            }
        }

        return $this->selector->select($candidates);
    }

    private function entitlementCandidate(
        ConferenceDiscountEntitlement $rule,
        EligibilityType $type,
        CarbonInterface $at,
    ): EligibilityCandidate {
        $rejection = RuleValidity::rejectionReason(
            (bool) $rule->active,
            $rule->valid_from,
            $rule->valid_until,
            $rule->maximum_uses,
            (int) $rule->uses_count,
            $at,
        );

        return new EligibilityCandidate(
            type: $type,
            id: (int) $rule->getKey(),
            percentageBasisPoints: (int) $rule->percentage_basis_points,
            reason: (string) $rule->reason,
            eligible: $rejection === null,
            rejectionReason: $rejection,
            context: [
                'source_type' => $rule->source_type,
                'source_reference' => $rule->source_reference,
            ],
        );
    }

    private function applyLock(Builder $query, bool $lock): void
    {
        if ($lock) {
            $query->lockForUpdate();
        }
    }
}
