<?php

namespace Database\Factories;

use App\Models\Purchase;
use App\Models\Tool;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Purchase>
 */
class PurchaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'tool_id' => Tool::factory(),
            'amount' => 19.99,
            'status' => Purchase::STATUS_ACTIVE,
        ];
    }
}
