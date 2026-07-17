<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Managers;

use App\Interfaces\HasPayment;
use App\Managers\PaymentManager;
use App\Models\PaymentFee;
use App\Models\User;
use Carbon\Carbon;
use ConferenceDiscountEligibility\Services\AuditLogger;
use ConferenceDiscountEligibility\Services\PaymentDiscountService;
use ConferenceDiscountEligibility\Services\SnapshotService;
use ConferenceDiscountEligibility\Support\DiscountablePaymentTypes;
use ConferenceDiscountEligibility\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class DiscountAwarePaymentManager extends PaymentManager
{
    public function __construct(
        private readonly PaymentDiscountService $discounts,
        private readonly SnapshotService $snapshots,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function queue(
        Model&HasPayment $model,
        PaymentFee $paymentFee,
        ?User $user,
        int $type,
        string $title,
        string $requestUrl,
        ?string $description = null,
        ?float $amount = null,
        ?string $currency = null,
        ?Carbon $expiredAt = null,
        array $additionalItems = [],
        ?float $baseAmount = null,
    ) {
        if (! DiscountablePaymentTypes::contains($type)) {
            return parent::queue($model, $paymentFee, $user, $type, $title, $requestUrl, $description, $amount, $currency, $expiredAt, $additionalItems, $baseAmount);
        }

        $scheduledConferenceId = (int) ($paymentFee->scheduled_conference_id ?: app()->getCurrentScheduledConference()?->getKey());
        if ($scheduledConferenceId <= 0) {
            return parent::queue($model, $paymentFee, $user, $type, $title, $requestUrl, $description, $amount, $currency, $expiredAt, $additionalItems, $baseAmount);
        }

        return DB::transaction(function () use ($model, $paymentFee, $user, $type, $title, $requestUrl, $description, $amount, $currency, $expiredAt, $additionalItems, $baseAmount, $scheduledConferenceId) {
            $resolvedCurrency = Money::assertSupportedCurrency((string) ($currency ?? $paymentFee->currency));
            $baseMinor = Money::toMinor((string) ($baseAmount ?? $paymentFee->amount), $resolvedCurrency);
            $totalMinor = Money::toMinor((string) ($amount ?? $paymentFee->amount), $resolvedCurrency);
            $prepared = $this->discounts->prepare(
                $scheduledConferenceId,
                $user,
                $baseMinor,
                $totalMinor,
                $additionalItems,
                $resolvedCurrency,
                true,
            );

            if (! $prepared->selection->hasDiscount()) {
                $payment = parent::queue($model, $paymentFee, $user, $type, $title, $requestUrl, $description, $amount, $currency, $expiredAt, $additionalItems, $baseAmount);
                if ($prepared->selection->evaluated !== []) {
                    $this->auditLogger->log(
                        action: 'discount_not_applied',
                        scheduledConferenceId: $scheduledConferenceId,
                        auditable: $payment,
                        affectedUserId: $user?->getKey(),
                        context: ['evaluated_rules' => $prepared->selection->evaluatedAsArray()],
                        origin: 'payment_queue',
                    );
                }
                return $payment;
            }

            $payment = parent::queue(
                model: $model,
                paymentFee: $paymentFee,
                user: $user,
                type: $type,
                title: $title,
                requestUrl: $requestUrl,
                description: $description,
                amount: Money::decimalFloat($prepared->calculation->finalTotalMinor, $resolvedCurrency),
                currency: $resolvedCurrency,
                expiredAt: $expiredAt,
                additionalItems: $prepared->additionalItems,
                baseAmount: Money::decimalFloat($baseMinor, $resolvedCurrency),
            );
            $snapshot = $this->snapshots->record($payment, $prepared);
            $this->auditLogger->log(
                action: 'discount_applied',
                scheduledConferenceId: $scheduledConferenceId,
                auditable: $payment,
                affectedUserId: $user?->getKey(),
                newValues: $snapshot->toArray(),
                context: ['evaluated_rules' => $prepared->selection->evaluatedAsArray()],
                origin: 'payment_queue',
            );
            return $payment;
        });
    }
}
