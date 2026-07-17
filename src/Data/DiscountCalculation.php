<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Data;

final class DiscountCalculation
{
    /** @param list<array<string, mixed>> $cleanAdditionalItems */
    public function __construct(
        public int $originalBaseMinor,
        public int $baseDiscountMinor,
        public int $finalBaseMinor,
        public int $addOnAmountMinor,
        public int $eligibleAddOnAmountMinor,
        public int $addOnDiscountMinor,
        public int $discountAmountMinor,
        public int $originalTotalMinor,
        public int $finalTotalMinor,
        public array $cleanAdditionalItems,
    ) {}

    /** @return array<string, int> */
    public function toArray(): array
    {
        return [
            'original_base_amount_minor' => $this->originalBaseMinor,
            'base_discount_amount_minor' => $this->baseDiscountMinor,
            'final_base_amount_minor' => $this->finalBaseMinor,
            'add_on_amount_minor' => $this->addOnAmountMinor,
            'eligible_add_on_amount_minor' => $this->eligibleAddOnAmountMinor,
            'add_on_discount_amount_minor' => $this->addOnDiscountMinor,
            'discount_amount_minor' => $this->discountAmountMinor,
            'original_total_amount_minor' => $this->originalTotalMinor,
            'final_total_amount_minor' => $this->finalTotalMinor,
        ];
    }
}
