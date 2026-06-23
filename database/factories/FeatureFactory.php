<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Feature>
 */
class FeatureFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ['en' => $this->faker->sentence(), 'ar' => $this->faker->sentence()], // Multi-language support
            'plan_id' => Plan::get()->random()->id, // Associate with a plan using Plan factory
            'is_enabled' => $this->faker->boolean(), // Random true/false
        ];
    }
}
