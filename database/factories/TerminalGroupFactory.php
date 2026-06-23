<?php

namespace Database\Factories;

use App\Models\Merchant;
use App\Models\TerminalGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TerminalGroup>
 */
class TerminalGroupFactory extends Factory
{
    protected $model = TerminalGroup::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'group_id' => TerminalGroup::generateGroupId(),
            'merchant_id' => Merchant::factory(),
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
