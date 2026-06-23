<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'service_id' => Service::factory(),
            'name' => ['en' => fake()->words(2, true), 'ar' => fake()->words(2, true)],
            'description' => ['en' => fake()->sentence(), 'ar' => fake()->sentence()],
            'status' => true,
        ];
    }
}
