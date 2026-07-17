<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Services;

use App\Managers\PaymentManager;
use App\Models\Payment;
use App\Notifications\PaymentConfirmed;
use ConferenceDiscountEligibility\Support\FullDiscountPolicy;
use ConferenceDiscountEligibility\Support\Money;
use ConferenceDiscountEligibility\Support\PaymentSafety;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class FullDiscountSettlementService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function settleIfZero(
        Payment $payment,
        int $finalTotalMinor,
        string $currency,
        string $origin,
        ?int $paidByUserId = null,
    ): Payment {
        if (! FullDiscountPolicy::shouldSettle($finalTotalMinor)) {
            return $payment;
        }

        return DB::transaction(function () use ($payment, $currency, $origin, $paidByUserId): Payment {
            $locked = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());
            if ($locked->isPaid()) {
                return $locked;
            }
            if (! PaymentSafety::canRecalculate($locked)) {
                throw new RuntimeException(
                    __('ConferenceDiscountEligibility::messages.full_discount_settlement_not_safe'),
                );
            }

            $resolvedCurrency = Money::assertSupportedCurrency($currency);
            $persistedTotalMinor = Money::toMinor((string) $locked->amount, $resolvedCurrency);
            if ($persistedTotalMinor !== 0) {
                throw new RuntimeException(
                    __('ConferenceDiscountEligibility::messages.full_discount_total_not_zero'),
                );
            }

            $locked->ensureInvoice();
            $scheduledConference = $locked->scheduledConference;
            if ($scheduledConference?->isReceiptEnabled() && ! $locked->receipt) {
                $receiptNumber = $scheduledConference->getLatestReceiptNumber();
                $locked->forceFill([
                    'receipt' => $scheduledConference->generateReceiptNumber($receiptNumber),
                ])->save();
                $scheduledConference->updateLatestReceiptNumber($receiptNumber + 1);
            }

            PaymentManager::get()->fulfillQueued(
                $locked,
                FullDiscountPolicy::PAYMENT_METHOD,
                $paidByUserId ?? ($locked->user_id ? (int) $locked->user_id : null),
            );
            $locked->setMeta('conference_discount_settlement', [
                'type' => FullDiscountPolicy::PAYMENT_METHOD,
                'origin' => $origin,
                'settled_at' => now()->toIso8601String(),
                'final_total_minor' => 0,
                'currency' => $resolvedCurrency,
                'gateway_required' => false,
            ]);

            $settled = $locked->refresh();
            $this->auditLogger->log(
                action: 'payment_completed_by_full_discount',
                scheduledConferenceId: (int) $settled->scheduled_conference_id,
                auditable: $settled,
                affectedUserId: $settled->user_id,
                newValues: [
                    'paid_at' => $settled->paid_at?->toIso8601String(),
                    'payment_method' => $settled->payment_method,
                    'amount' => $settled->amount,
                    'currency' => $settled->currency,
                ],
                context: [
                    'gateway_required' => false,
                    'final_total_minor' => 0,
                ],
                origin: $origin,
            );

            $paymentId = (int) $settled->getKey();
            DB::afterCommit(static function () use ($paymentId): void {
                $confirmed = Payment::query()->with('user')->find($paymentId);
                if ($confirmed?->user !== null) {
                    $confirmed->user->notify(new PaymentConfirmed($confirmed));
                }
            });

            return $settled;
        });
    }
}
