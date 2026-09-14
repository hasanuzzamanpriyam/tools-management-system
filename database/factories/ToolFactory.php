<?php

namespace Database\Factories;

use App\Models\Tool;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tool>
 */
class ToolFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(),
            'type' => Tool::TYPE_EXTENSION,
            'pricing_model' => Tool::PRICING_ONE_TIME,
            'price' => fake()->randomFloat(2, 5, 99),
            'device_limit' => 1,
            'referral_credits' => 0,
            'is_active' => true,
        ];
    }
}
