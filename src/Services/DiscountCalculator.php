<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Services;

use ConferenceDiscountEligibility\Data\DiscountCalculation;
use ConferenceDiscountEligibility\Enums\DiscountScope;
use ConferenceDiscountEligibility\Support\Money;
use ConferenceDiscountEligibility\Support\Percentage;

final class DiscountCalculator
{
    public const DISCOUNT_ITEM_KEY = 'conference_discount_eligibility';
    public const CALCULATION_VERSION = '1';

    /** @param list<array<string, mixed>> $additionalItems @param list<string> $eligibleAddOnKeys */
    public function calculate(int $originalBaseMinor, array $additionalItems, int $percentageBasisPoints, DiscountScope $scope, array $eligibleAddOnKeys, string $currency, ?int $declaredTotalMinor = null): DiscountCalculation
    {
        Percentage::assertBasisPoints($percentageBasisPoints, true);
        Money::assertSupportedCurrency($currency);
        if ($originalBaseMinor < 0) { throw new \InvalidArgumentException('Base amount cannot be negative.'); }
        $cleanItems = [];
        $listedAddOnAmountMinor = 0;
        $eligibleAddOnAmountMinor = 0;
        $eligibleAddOnKeys = array_values(array_unique(array_map('strval', $eligibleAddOnKeys)));
        foreach ($additionalItems as $item) {
            if (($item['key'] ?? null) === self::DISCOUNT_ITEM_KEY || ($item['cde_discount_line'] ?? false) === true) { continue; }
            $quantity = max(0, (int) ($item['quantity'] ?? 1));
            $itemTotal = array_key_exists('total_amount', $item)
                ? Money::toMinor((string) $item['total_amount'], $currency)
                : Money::toMinor((string) ($item['amount'] ?? '0'), $currency) * $quantity;
            if ($itemTotal < 0) { throw new \InvalidArgumentException('Add-on amount cannot be negative.'); }
            $item['quantity'] = $quantity;
            $item['total_amount'] = Money::decimalFloat($itemTotal, $currency);
            $cleanItems[] = $item;
            $listedAddOnAmountMinor += $itemTotal;
            if ($scope === DiscountScope::BaseFeeAndEligibleAddOns && in_array((string) ($item['key'] ?? ''), $eligibleAddOnKeys, true)) { $eligibleAddOnAmountMinor += $itemTotal; }
        }
        $computedTotalMinor = $originalBaseMinor + $listedAddOnAmountMinor;
        $originalTotalMinor = $declaredTotalMinor ?? $computedTotalMinor;
        if ($originalTotalMinor < $originalBaseMinor) { throw new \InvalidArgumentException('Declared total cannot be less than the base amount.'); }
        $addOnAmountMinor = $originalTotalMinor - $originalBaseMinor;
        $eligibleAddOnAmountMinor = min($eligibleAddOnAmountMinor, $addOnAmountMinor);
        $baseDiscountMinor = Money::multiplyBasisPoints($originalBaseMinor, $percentageBasisPoints);
        $addOnDiscountMinor = Money::multiplyBasisPoints($eligibleAddOnAmountMinor, $percentageBasisPoints);
        $discountAmountMinor = min($originalTotalMinor, $baseDiscountMinor + $addOnDiscountMinor);
        return new DiscountCalculation(
            originalBaseMinor: $originalBaseMinor,
            baseDiscountMinor: $baseDiscountMinor,
            finalBaseMinor: max(0, $originalBaseMinor - $baseDiscountMinor),
            addOnAmountMinor: $addOnAmountMinor,
            eligibleAddOnAmountMinor: $eligibleAddOnAmountMinor,
            addOnDiscountMinor: $addOnDiscountMinor,
            discountAmountMinor: $discountAmountMinor,
            originalTotalMinor: $originalTotalMinor,
            finalTotalMinor: max(0, $originalTotalMinor - $discountAmountMinor),
            cleanAdditionalItems: $cleanItems,
        );
    }
}
