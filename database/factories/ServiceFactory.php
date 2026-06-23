<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'category_id' => ServiceCategory::factory(),
            'service_type' => 'digital',
            'service_name' => ['en' => fake()->words(3, true), 'ar' => fake()->words(3, true)],
            'status' => 'active',
            'is_active' => true,
        ];
    }
}
