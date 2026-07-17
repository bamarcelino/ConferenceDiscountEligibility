<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Database\Factories;

use ConferenceDiscountEligibility\Models\ConferenceDiscountDomain;
use Illuminate\Database\Eloquent\Factories\Factory;

final class ConferenceDiscountDomainFactory extends Factory
{
    protected $model = ConferenceDiscountDomain::class;

    public function definition(): array
    {
        return [
            'original_domain' => $this->faker->unique()->domainName(),
            'percentage_basis_points' => 3000,
            'reason' => 'Institutional partner affiliate',
            'include_subdomains' => false,
            'active' => true,
            'uses_count' => 0,
        ];
    }
}
