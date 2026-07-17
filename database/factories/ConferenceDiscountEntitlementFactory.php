<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Database\Factories;

use ConferenceDiscountEligibility\Models\ConferenceDiscountEntitlement;
use Illuminate\Database\Eloquent\Factories\Factory;

final class ConferenceDiscountEntitlementFactory extends Factory
{
    protected $model = ConferenceDiscountEntitlement::class;

    public function definition(): array
    {
        return [
            'eligibility_type' => 'email',
            'original_email' => $this->faker->unique()->safeEmail(),
            'percentage_basis_points' => 4000,
            'reason' => 'Individual approval',
            'active' => true,
            'source_type' => 'test',
            'uses_count' => 0,
        ];
    }
}
