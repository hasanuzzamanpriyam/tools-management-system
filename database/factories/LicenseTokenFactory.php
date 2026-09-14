<?php

namespace Database\Factories;

use App\Models\LicenseToken;
use App\Models\Purchase;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LicenseToken>
 */
class LicenseTokenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'purchase_id' => Purchase::factory(),
            'token_hash' => hash('sha256', Str::random(64)),
            'device_limit' => 1,
            'is_active' => true,
        ];
    }
}
