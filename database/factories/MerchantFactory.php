<?php

namespace Database\Factories;

use App\Models\Merchant;
use App\Models\User;
use App\Enums\BusinessType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Merchant>
 */
class MerchantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'owner_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'business_type' => fake()->randomElement([
                BusinessType::RETAIL->value,
                BusinessType::ELECTRONICS->value,
                BusinessType::PHARMACY->value,
                BusinessType::SERVICES->value,
            ]),
            'is_active' => fake()->boolean(80), // 80% chance of being active
            'logo' => null, // Will be set manually if needed
            'merchant_code' => Merchant::generateMerchantCode(),
            'user_id' => null, // Will be set manually if needed
        ];
    }

    /**
     * Indicate that the merchant is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the merchant is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Associate merchant with a user.
     */
    public function withUser(User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user ? $user->id : User::factory()->create()->id,
        ]);
    }

    /**
     * Set specific business type.
     */
    public function businessType(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'business_type' => $type,
        ]);
    }
} 