<?php

namespace Database\Factories;

use App\Models\Merchant;
use App\Models\Webhook;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Webhook>
 */
class WebhookFactory extends Factory
{
    protected $model = Webhook::class;

    public function definition(): array
    {
        return [
            'merchant_id' => Merchant::factory(),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'endpoint_url' => fake()->url(),
            'is_active' => true,
            'success_count' => 0,
            'failure_count' => 0,
        ];
    }
}
