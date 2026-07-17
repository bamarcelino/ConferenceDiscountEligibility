<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Services;

use App\Managers\PaymentManager;
use App\Models\Payment;
use App\Models\User;
use ConferenceDiscountEligibility\Data\DomainIdentityDecision;
use ConferenceDiscountEligibility\Enums\EligibilityType;
use ConferenceDiscountEligibility\Models\ConferenceDiscountDomain;
use ConferenceDiscountEligibility\Models\ConferenceDiscountEntitlement;
use ConferenceDiscountEligibility\Models\ConferenceDiscountPaymentSnapshot;
use ConferenceDiscountEligibility\Support\DomainMatcher;
use ConferenceDiscountEligibility\Support\EmailNormalizer;
use ConferenceDiscountEligibility\Support\PaymentSafety;
use InvalidArgumentException;
use Throwable;

final class RecalculationCoordinator
{
    public function __construct(
        private readonly UnpaidPaymentRecalculator $recalculator,
        private readonly EmailEntitlementLinker $linker,
        private readonly AuditLogger $auditLogger,
        private readonly DomainIdentityVerifier $domainIdentityVerifier,
    ) {}

    /**
     * @return array{
     *     candidates:int,
     *     matched:int,
     *     discounted:int,
     *     unchanged:int,
     *     skipped:int,
     *     paid:int,
     *     failed:int,
     *     unverified_domain_matches:int,
     *     confirmed_author_domain_matches:int
     * }
     */
    public function run(string $ruleModel, int $ruleId, bool $notify = false): array
    {
        $stats = [
            'candidates' => 0,
            'matched' => 0,
            'discounted' => 0,
            'unchanged' => 0,
            'skipped' => 0,
            'paid' => 0,
            'failed' => 0,
            'unverified_domain_matches' => 0,
            'confirmed_author_domain_matches' => 0,
        ];
        $paymentIds = [];
        $scheduledConferenceId = 0;
        $ruleContext = [
            'rule_model' => $ruleModel,
            'rule_id' => $ruleId,
            'notify' => $notify,
        ];

        if ($ruleModel === 'entitlement') {
            $rule = ConferenceDiscountEntitlement::query()->findOrFail($ruleId);
            $scheduledConferenceId = (int) $rule->scheduled_conference_id;

            if ($rule->eligibility_type === EligibilityType::Email->value && ! $rule->user_id) {
                $user = User::query()
                    ->whereRaw('LOWER(TRIM(email)) = ?', [$rule->normalized_email])
                    ->first();
                if ($user) {
                    $this->linker->link($user, $scheduledConferenceId);
                    $rule->refresh();
                }
            }

            $ruleContext += [
                'eligibility_type' => (string) $rule->eligibility_type,
                'rule_user_id' => $rule->user_id ? (int) $rule->user_id : null,
                'rule_email' => $rule->user?->email ?? $rule->normalized_email,
            ];

            if ($rule->user_id) {
                $paymentIds = Payment::query()
                    ->where('scheduled_conference_id', $scheduledConferenceId)
                    ->where('type', PaymentManager::TYPE_PARTICIPANT_FEE)
                    ->where('user_id', $rule->user_id)
                    ->pluck('id')
                    ->map(fn ($id): int => (int) $id)
                    ->all();
            }
        } elseif ($ruleModel === 'domain') {
            $rule = ConferenceDiscountDomain::query()->findOrFail($ruleId);
            $scheduledConferenceId = (int) $rule->scheduled_conference_id;
            $policy = $rule->identityPolicy();
            $ruleContext += [
                'rule_domain' => (string) $rule->normalized_domain,
                'include_subdomains' => (bool) $rule->include_subdomains,
                'identity_policy' => $policy->value,
            ];

            /** @var array<int, DomainIdentityDecision> $identityDecisions */
            $identityDecisions = [];

            Payment::query()
                ->where('scheduled_conference_id', $scheduledConferenceId)
                ->where('type', PaymentManager::TYPE_PARTICIPANT_FEE)
                ->with('user')
                ->chunkById(200, function ($payments) use (
                    $rule,
                    $policy,
                    $scheduledConferenceId,
                    &$paymentIds,
                    &$stats,
                    &$identityDecisions,
                ): void {
                    foreach ($payments as $payment) {
                        $user = $payment->user;
                        $domain = EmailNormalizer::domain($user?->email);
                        if (
                            ! $domain
                            || ! DomainMatcher::matches(
                                $domain,
                                (string) $rule->normalized_domain,
                                (bool) $rule->include_subdomains,
                            )
                        ) {
                            continue;
                        }

                        $stats['candidates']++;
                        $userKey = (int) ($user?->getKey() ?? 0);
                        $decision = $identityDecisions[$userKey]
                            ??= $this->domainIdentityVerifier->evaluate(
                                $policy,
                                $scheduledConferenceId,
                                $user,
                            );

                        if (! $decision->eligible) {
                            $stats['unverified_domain_matches']++;
                            continue;
                        }

                        if ($decision->usedAuthorEvidence()) {
                            $stats['confirmed_author_domain_matches']++;
                        }

                        $paymentIds[] = (int) $payment->getKey();
                    }
                });
        } else {
            throw new InvalidArgumentException('Unknown recalculation rule model.');
        }

        $paymentIds = array_values(array_unique($paymentIds));
        if ($ruleModel === 'entitlement') {
            $stats['candidates'] = count($paymentIds);
        }
        $stats['matched'] = count($paymentIds);

        foreach ($paymentIds as $paymentId) {
            $payment = Payment::query()->find($paymentId);
            if (! $payment) {
                $stats['skipped']++;
                continue;
            }
            if ($payment->isPaid()) {
                $stats['paid']++;
                continue;
            }
            if (! PaymentSafety::canRecalculate($payment)) {
                $stats['skipped']++;
                continue;
            }

            try {
                $updated = $this->recalculator->recalculate(
                    $payment,
                    $notify,
                    'rule_recalculation',
                );
                $snapshot = ConferenceDiscountPaymentSnapshot::query()
                    ->where('payment_id', $updated->getKey())
                    ->first();

                if ((int) ($snapshot?->discount_amount_minor ?? 0) > 0) {
                    $stats['discounted']++;
                } else {
                    $stats['unchanged']++;
                }
            } catch (Throwable $exception) {
                $stats['failed']++;
                report($exception);
                $this->auditLogger->log(
                    action: 'payment_recalculation_failed',
                    scheduledConferenceId: $scheduledConferenceId,
                    auditable: $payment,
                    affectedUserId: $payment->user_id,
                    context: [
                        'exception' => class_basename($exception),
                        'message_fingerprint' => hash('sha256', $exception->getMessage()),
                    ],
                    origin: 'recalculation_job',
                );
            }
        }

        $this->auditLogger->log(
            action: 'rule_payment_recalculation_completed',
            scheduledConferenceId: $scheduledConferenceId,
            newValues: $stats,
            context: $ruleContext,
            origin: 'recalculation_job',
        );

        return $stats;
    }
}
