<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ServiceCategory>
 */
class ServiceCategoryFactory extends Factory
{
    protected $model = ServiceCategory::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'type' => 'service',
            'name_en' => fake()->words(2, true),
            'name_ar' => fake()->words(2, true),
            'code' => 'CAT_' . strtoupper(Str::random(6)),
            'is_active' => true,
        ];
    }
}
