<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Data;

final class PreparedPaymentDiscount
{
    /** @param list<array<string, mixed>> $additionalItems */
    public function __construct(
        public EligibilitySelection $selection,
        public DiscountCalculation $calculation,
        public string $currency,
        public array $additionalItems,
    ) {}
}
