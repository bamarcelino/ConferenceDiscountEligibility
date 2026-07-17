<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Services;

use App\Managers\PaymentManager;
use App\Models\Payment;
use App\Models\User;
use ConferenceDiscountEligibility\Enums\EligibilityType;
use ConferenceDiscountEligibility\Models\ConferenceDiscountDomain;
use ConferenceDiscountEligibility\Models\ConferenceDiscountEntitlement;
use ConferenceDiscountEligibility\Support\DomainMatcher;
use ConferenceDiscountEligibility\Support\EmailNormalizer;
use ConferenceDiscountEligibility\Support\PaymentSafety;

final class RecalculationCoordinator
{
    public function __construct(
        private readonly UnpaidPaymentRecalculator $recalculator,
        private readonly EmailEntitlementLinker $linker,
        private readonly AuditLogger $auditLogger,
    ) {}

    /** @return array{recalculated:int,skipped:int,paid:int,failed:int} */
    public function run(string $ruleModel, int $ruleId, bool $notify = false): array
    {
        $stats = ['recalculated' => 0, 'skipped' => 0, 'paid' => 0, 'failed' => 0];
        $paymentIds = [];
        $scheduledConferenceId = 0;

        if ($ruleModel === 'entitlement') {
            $rule = ConferenceDiscountEntitlement::query()->findOrFail($ruleId);
            $scheduledConferenceId = (int) $rule->scheduled_conference_id;
            if ($rule->eligibility_type === EligibilityType::Email->value && ! $rule->user_id) {
                $user = User::query()->whereRaw('LOWER(email) = ?', [$rule->normalized_email])->first();
                if ($user) { $this->linker->link($user, $scheduledConferenceId); $rule->refresh(); }
            }
            if ($rule->user_id) {
                $paymentIds = Payment::query()
                    ->where('scheduled_conference_id', $scheduledConferenceId)
                    ->where('type', PaymentManager::TYPE_PARTICIPANT_FEE)
                    ->where('user_id', $rule->user_id)
                    ->pluck('id')->map(fn ($id) => (int) $id)->all();
            }
        } elseif ($ruleModel === 'domain') {
            $rule = ConferenceDiscountDomain::query()->findOrFail($ruleId);
            $scheduledConferenceId = (int) $rule->scheduled_conference_id;
            Payment::query()
                ->where('scheduled_conference_id', $scheduledConferenceId)
                ->where('type', PaymentManager::TYPE_PARTICIPANT_FEE)
                ->with('user')
                ->chunkById(200, function ($payments) use ($rule, &$paymentIds): void {
                    foreach ($payments as $payment) {
                        $user = $payment->user;
                        $domain = EmailNormalizer::domain($user?->email);
                        if ($user?->hasVerifiedEmail() && $domain && DomainMatcher::matches($domain, $rule->normalized_domain, (bool) $rule->include_subdomains)) {
                            $paymentIds[] = (int) $payment->getKey();
                        }
                    }
                });
        } else {
            throw new \InvalidArgumentException('Unknown recalculation rule model.');
        }

        foreach (array_values(array_unique($paymentIds)) as $paymentId) {
            $payment = Payment::query()->find($paymentId);
            if (! $payment) { $stats['skipped']++; continue; }
            if ($payment->isPaid()) { $stats['paid']++; continue; }
            if (! PaymentSafety::canRecalculate($payment)) { $stats['skipped']++; continue; }
            try {
                $this->recalculator->recalculate($payment, $notify, 'rule_recalculation');
                $stats['recalculated']++;
            } catch (\Throwable) {
                $stats['failed']++;
            }
        }

        $this->auditLogger->log(
            action: 'rule_payment_recalculation_completed',
            scheduledConferenceId: $scheduledConferenceId,
            newValues: $stats,
            context: ['rule_model' => $ruleModel, 'rule_id' => $ruleId, 'notify' => $notify],
            origin: 'recalculation_job',
        );
        return $stats;
    }
}
