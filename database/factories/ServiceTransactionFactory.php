<?php

namespace Database\Factories;

use App\Models\Merchant;
use App\Models\ServiceTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceTransaction>
 */
class ServiceTransactionFactory extends Factory
{
    protected $model = ServiceTransaction::class;

    public function definition(): array
    {
        return [
            'merchant_id' => Merchant::factory(),
            'transaction_id' => 'SRV-' . fake()->unique()->numerify('########'),
            'status' => 'pending',
            'request_payload' => [],
            'service_response' => [],
        ];
    }
}
