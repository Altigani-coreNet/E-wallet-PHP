<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Terminal;

class TerminalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $manufacturers = ['Verifone', 'Ingenico', 'PAX', 'Poynt', 'Square', 'Clover', 'First Data'];
        $models = ['VX520', 'P400', 'A920', 'Poynt 5', 'Square Terminal', 'Clover Station', 'FD130'];
        $brands = ['Verifone', 'Ingenico', 'PAX', 'Poynt', 'Square', 'Clover', 'First Data', 'NCR', 'Toshiba', 'Honeywell'];
        $sdkVersions = ['1.0.0', '1.1.0', '1.2.0', '2.0.0', '2.1.0', '3.0.0'];
        $androidVersions = ['Android 8.0', 'Android 9.0', 'Android 10.0', 'Android 11.0', 'Android 12.0', 'Android 13.0'];
        $addTypes = ['auto', 'static'];
        $terminalStatuses = ['online', 'offline', 'testing'];

        // Get all merchants for assignment
        $merchants = \App\Models\Merchant::all();
        
        if ($merchants->isEmpty()) {
            // Create a default merchant if none exist
            $merchant = \App\Models\Merchant::factory()->create();
            $merchants = collect([$merchant]);
        }

        // Create exactly 30 terminals
        for ($i = 1; $i <= 30; $i++) {
            Terminal::create([
                'name' => "Terminal {$i}",
                'terminal_id' => Terminal::generateTerminalId(),
                'merchant_id' => $merchants->random()->id,
                'brand' => $brands[array_rand($brands)],
                'model' => $models[array_rand($models)],
                'manufacturer' => $manufacturers[array_rand($manufacturers)],
                'serial_no' => 'SN' . strtoupper(substr(md5(uniqid()), 0, 10)),
                'sdk_id' => 'SDK' . strtoupper(substr(md5(uniqid()), 0, 8)),
                'sdk_version' => $sdkVersions[array_rand($sdkVersions)],
                'android_os' => $androidVersions[array_rand($androidVersions)],
                'add_type' => $addTypes[array_rand($addTypes)],
                'terminal_status' => $terminalStatuses[array_rand($terminalStatuses)],
                'is_active' => rand(0, 1),
            ]);
        }

        $this->command->info('Terminals seeded successfully!');
    }
}
