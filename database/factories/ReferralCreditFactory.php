<?php

namespace Database\Factories;

use App\Models\Purchase;
use App\Models\ReferralCredit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReferralCredit>
 */
class ReferralCreditFactory extends Factory
{
    public function definition(): array
    {
        return [
            'referrer_id' => User::factory(),
            'purchase_id' => Purchase::factory(),
            'amount' => 5.00,
        ];
    }
}
