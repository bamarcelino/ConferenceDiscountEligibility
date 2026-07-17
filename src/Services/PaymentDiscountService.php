<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Services;

use App\Models\Payment;
use App\Models\User;
use ConferenceDiscountEligibility\Data\DiscountCalculation;
use ConferenceDiscountEligibility\Data\EligibilitySelection;
use ConferenceDiscountEligibility\Data\PreparedPaymentDiscount;
use ConferenceDiscountEligibility\Enums\DiscountScope;
use ConferenceDiscountEligibility\Models\ConferenceDiscountPaymentSnapshot;
use ConferenceDiscountEligibility\Support\Money;
use ConferenceDiscountEligibility\Support\Percentage;

final class PaymentDiscountService
{
    public function __construct(
        private readonly EligibilityResolver $resolver,
        private readonly DiscountCalculator $calculator,
        private readonly SettingsRepository $settings,
    ) {}

    /** @param list<array<string, mixed>> $additionalItems */
    public function prepare(
        int $scheduledConferenceId,
        ?User $user,
        int $baseMinor,
        int $originalTotalMinor,
        array $additionalItems,
        string $currency,
        bool $lock = false,
    ): PreparedPaymentDiscount {
        $selection = $this->resolver->resolve($scheduledConferenceId, $user, $user?->email, now(), $lock);
        return $this->prepareForSelection($scheduledConferenceId, $selection, $baseMinor, $originalTotalMinor, $additionalItems, $currency);
    }

    /** @param list<array<string, mixed>> $additionalItems */
    public function prepareForSelection(
        int $scheduledConferenceId,
        EligibilitySelection $selection,
        int $baseMinor,
        int $originalTotalMinor,
        array $additionalItems,
        string $currency,
    ): PreparedPaymentDiscount {
        $percentage = $selection->winner?->percentageBasisPoints ?? 0;
        $scope = $this->settings->scope($scheduledConferenceId);
        $eligibleKeys = $this->settings->eligibleAddOnKeys($scheduledConferenceId);
        $calculation = $this->calculator->calculate($baseMinor, $additionalItems, $percentage, $scope, $eligibleKeys, $currency, $originalTotalMinor);
        $items = $calculation->cleanAdditionalItems;
        if ($calculation->discountAmountMinor > 0 && $selection->winner !== null) {
            $items[] = $this->discountLine($calculation, $selection, $currency);
        }
        return new PreparedPaymentDiscount($selection, $calculation, $currency, $items);
    }

    /** @param list<array<string, mixed>> $additionalItems */
    public function prepareFromSnapshot(
        ConferenceDiscountPaymentSnapshot $snapshot,
        int $baseMinor,
        int $originalTotalMinor,
        array $additionalItems,
        string $currency,
    ): PreparedPaymentDiscount {
        $metadata = is_array($snapshot->metadata) ? $snapshot->metadata : [];
        $scope = DiscountScope::tryFrom((string) ($metadata['discount_scope'] ?? 'base_only')) ?? DiscountScope::BaseRegistrationFeeOnly;
        $eligibleKeys = is_array($metadata['eligible_add_on_keys'] ?? null) ? $metadata['eligible_add_on_keys'] : [];
        $selection = SnapshotService::selectionFromSnapshot($snapshot);
        $calculation = $this->calculator->calculate(
            $baseMinor,
            $additionalItems,
            (int) $snapshot->discount_percentage_basis_points,
            $scope,
            $eligibleKeys,
            $currency,
            $originalTotalMinor,
        );
        $items = $calculation->cleanAdditionalItems;
        if ($calculation->discountAmountMinor > 0 && $selection->winner !== null) {
            $items[] = $this->discountLine($calculation, $selection, $currency);
        }
        return new PreparedPaymentDiscount($selection, $calculation, $currency, $items);
    }

    /** @return array<string, mixed> */
    private function discountLine(DiscountCalculation $calculation, EligibilitySelection $selection, string $currency): array
    {
        $winner = $selection->winner;
        return [
            'key' => DiscountCalculator::DISCOUNT_ITEM_KEY,
            'name' => __('ConferenceDiscountEligibility::messages.discount_line', [
                'percentage' => Percentage::format((int) $winner?->percentageBasisPoints),
            ]),
            'description' => $winner?->reason,
            'amount' => Money::decimalFloat(-$calculation->discountAmountMinor, $currency),
            'quantity' => 1,
            'total_amount' => Money::decimalFloat(-$calculation->discountAmountMinor, $currency),
            'cde_discount_line' => true,
        ];
    }

    /** @return list<array<string, mixed>> */
    public function cleanAdditionalItems(Payment $payment): array
    {
        $items = $payment->getMeta('additional_items', []);
        return array_values(array_filter(is_array($items) ? $items : [], static fn (mixed $item): bool =>
            is_array($item)
            && ($item['key'] ?? null) !== DiscountCalculator::DISCOUNT_ITEM_KEY
            && ($item['cde_discount_line'] ?? false) !== true
        ));
    }
}
