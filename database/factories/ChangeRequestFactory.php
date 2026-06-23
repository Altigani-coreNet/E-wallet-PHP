<?php

namespace Database\Factories;

use App\Models\ChangeRequest;
use App\Models\Merchant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChangeRequest>
 */
class ChangeRequestFactory extends Factory
{
    protected $model = ChangeRequest::class;

    public function definition(): array
    {
        $merchant = Merchant::factory()->create();

        return [
            'changeable_type' => Merchant::class,
            'changeable_id' => $merchant->id,
            'requester_type' => Merchant::class,
            'requester_id' => $merchant->id,
            'payload' => ['field' => 'name', 'value' => fake()->company()],
            'reason' => fake()->sentence(),
            'status' => 'pending',
            'has_file' => false,
        ];
    }
}
