<?php

namespace Database\Factories;

use App\Models\UserGroup;
use App\Models\Merchant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserGroup>
 */
class UserGroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'group_id' => UserGroup::generateGroupId(),
            'merchant_id' => Merchant::factory(),
            'description' => $this->faker->sentence(),
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
            'is_single_terminal' => $this->faker->boolean(30), // 30% chance of single terminal mode
            'terminal_id' => null, // Will be set in the factory if needed
        ];
    }

    /**
     * Indicate that the user group is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the user group is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the user group uses single terminal mode.
     */
    public function singleTerminal(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_single_terminal' => true,
        ]);
    }

    /**
     * Indicate that the user group uses multiple terminal groups mode.
     */
    public function multipleTerminalGroups(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_single_terminal' => false,
        ]);
    }
} 