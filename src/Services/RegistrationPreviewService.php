<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Services;

use App\Managers\PaymentManager;
use App\Models\PaymentFee;
use App\Models\User;
use ConferenceDiscountEligibility\Support\Money;
use ConferenceDiscountEligibility\Support\Percentage;

final class RegistrationPreviewService
{
    public function __construct(
        private readonly EligibilityResolver $resolver,
        private readonly PaymentDiscountService $discounts,
    ) {}

    /** @return array<string, mixed> */
    public function build(int $scheduledConferenceId, User $user): array
    {
        // Eligibility is user/conference scoped, so resolve it once rather than once per fee.
        $selection = $this->resolver->resolve($scheduledConferenceId, $user, $user->email, now(), false);
        if (! $selection->hasDiscount()) {
            return ['eligible' => false, 'winner' => null, 'rows' => []];
        }

        $fees = PaymentFee::query()
            ->where('scheduled_conference_id', $scheduledConferenceId)
            ->type(PaymentManager::TYPE_PARTICIPANT_FEE)
            ->active()
            ->orderBy('name')
            ->get();

        $rows = [];
        foreach ($fees as $fee) {
            $currency = strtoupper((string) $fee->currency);
            $baseMinor = Money::toMinor((string) $fee->amount, $currency);
            $prepared = $this->discounts->prepareForSelection(
                $scheduledConferenceId,
                $selection,
                $baseMinor,
                $baseMinor,
                [],
                $currency,
            );
            $rows[] = [
                'category' => $fee->name,
                'currency' => $currency,
                'standard' => Money::decimal($baseMinor, $currency),
                'discount_percentage' => Percentage::format($selection->winner?->percentageBasisPoints ?? 0),
                'discount' => Money::decimal($prepared->calculation->discountAmountMinor, $currency),
                'final' => Money::decimal($prepared->calculation->finalTotalMinor, $currency),
                'reason' => $selection->winner?->reason,
            ];
        }

        return ['eligible' => true, 'winner' => $selection->winner, 'rows' => $rows];
    }
}
