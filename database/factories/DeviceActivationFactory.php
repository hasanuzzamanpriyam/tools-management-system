<?php

namespace Database\Factories;

use App\Models\DeviceActivation;
use App\Models\LicenseToken;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DeviceActivation>
 */
class DeviceActivationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'license_token_id' => LicenseToken::factory(),
            'device_fingerprint' => Str::random(32),
            'activated_at' => now(),
        ];
    }
}
