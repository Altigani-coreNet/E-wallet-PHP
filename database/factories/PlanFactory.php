<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Plan>
 */
class PlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "name" => [
                "ar" => $this->faker->word(),
                "en" => $this->faker->word(),
            ],
            "description" => [
                "ar" => $this->faker->sentence(),
                "en" => $this->faker->sentence(),
             ],
            'price'          => $this->faker->randomFloat(2, 10, 500), // Random price between 10 and 500
            'stripe_id'      => $this->faker->uuid(),
            'status'         => $this->faker->boolean(),
            'has_discount'   => $this->faker->boolean(),
            'current_price'  => $this->faker->randomFloat(2, 5, 300), // Discounted price
        ];
    }
}
