<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Merchant;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Terminal>
 */
class TerminalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $manufacturers = ['Verifone', 'Ingenico', 'PAX', 'Poynt', 'Square', 'Clover', 'First Data'];
        $models = ['VX520', 'P400', 'A920', 'Poynt 5', 'Square Terminal', 'Clover Station', 'FD130'];
        $brands = ['Verifone', 'Ingenico', 'PAX', 'Poynt', 'Square', 'Clover', 'First Data', 'NCR', 'Toshiba', 'Honeywell'];
        $sdkVersions = ['1.0.0', '1.1.0', '1.2.0', '2.0.0', '2.1.0', '3.0.0'];
        $androidVersions = ['Android 8.0', 'Android 9.0', 'Android 10.0', 'Android 11.0', 'Android 12.0', 'Android 13.0'];
        $addTypes = ['auto', 'static'];
        $terminalStatuses = ['online', 'offline', 'testing'];
        
        return [
            'name' => $this->faker->words(2, true) . ' Terminal',
            'terminal_id' => 'TERM' . strtoupper($this->faker->regexify('[A-Z0-9]{8}')),
            'merchant_id' => Merchant::inRandomOrder()->first()?->id ?? Merchant::factory(),
            'brand' => $this->faker->randomElement($brands),
            'model' => $this->faker->randomElement($models),
            'manufacturer' => $this->faker->randomElement($manufacturers),
            'serial_no' => $this->faker->regexify('[A-Z0-9]{12}'),
            'sdk_id' => 'SDK' . strtoupper($this->faker->regexify('[A-Z0-9]{8}')),
            'sdk_version' => $this->faker->randomElement($sdkVersions),
            'android_os' => $this->faker->randomElement($androidVersions),
            'add_type' => $this->faker->randomElement($addTypes),
            'terminal_status' => $this->faker->randomElement($terminalStatuses),
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
        ];
    }

    /**
     * Indicate that the terminal is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the terminal is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
