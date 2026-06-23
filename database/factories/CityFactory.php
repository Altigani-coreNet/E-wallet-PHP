<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<City>
 */
class CityFactory extends Factory
{
    protected $model = City::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'country_id' => Country::factory(),
            'name' => ['en' => fake()->city(), 'ar' => fake()->city()],
            'status' => true,
        ];
    }
}
