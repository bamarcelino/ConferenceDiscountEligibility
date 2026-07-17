<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Database\Factories;

use ConferenceDiscountEligibility\Models\ConferenceDiscountCoupon;
use ConferenceDiscountEligibility\Support\CouponCode;
use ConferenceDiscountEligibility\Support\CouponPaymentTypes;
use Illuminate\Database\Eloquent\Factories\Factory;

final class ConferenceDiscountCouponFactory extends Factory
{
    protected $model = ConferenceDiscountCoupon::class;

    public function definition(): array
    {
        $code = CouponCode::generate('TEST');

        return [
            'name' => 'Test coupon campaign',
            'code_hash' => CouponCode::hash($code),
            'code_hint' => CouponCode::hint($code),
            'percentage_basis_points' => 4000,
            'reason' => 'Test coupon',
            'eligible_payment_types' => CouponPaymentTypes::all(),
            'eligible_payment_fee_ids' => null,
            'active' => true,
            'per_user_limit' => 1,
            'uses_count' => 0,
        ];
    }
}
