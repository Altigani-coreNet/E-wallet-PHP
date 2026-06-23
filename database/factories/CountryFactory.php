<?php

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Country>
 */
class CountryFactory extends Factory
{
    protected $model = Country::class;

    public function definition(): array
    {
        $code = strtoupper(fake()->unique()->lexify('??'));

        return [
            'id' => (string) Str::uuid(),
            'name' => ['en' => fake()->country(), 'ar' => fake()->country()],
            'short_name' => $code,
            'code' => $code,
            'status' => true,
        ];
    }
}
