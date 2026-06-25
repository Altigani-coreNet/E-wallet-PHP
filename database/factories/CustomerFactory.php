<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+2499'.fake()->unique()->numerify('#######'),
            'password' => Hash::make('Password1!'),
            'balance' => 0,
            'profile_completed' => false,
            'status' => \App\Models\Customer::STATUS_PENDING,
        ];
    }

    public function withCompletedProfile(): static
    {
        return $this->state(fn () => [
            'profile_completed' => true,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => Customer::STATUS_ACTIVE,
            'profile_completed' => true,
        ]);
    }
}
