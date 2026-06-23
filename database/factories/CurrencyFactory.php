<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory
{
    protected $model = \App\Models\Currency::class;

    public function definition(): array
    {
        $code = strtoupper(fake()->unique()->lexify('???'));

        return [
            'id' => (string) Str::uuid(),
            'country' => fake()->country(),
            'name' => $code,
            'symbol' => '$',
            'currency_code' => ['en' => $code, 'ar' => $code],
        ];
    }

    public function forCountry(?Country $country = null): static
    {
        return $this->state(fn () => [
            'country' => $country?->name['en'] ?? fake()->country(),
        ]);
    }
}
