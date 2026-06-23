<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TerminalGroup;
use App\Models\Merchant;
use App\Models\Terminal;
use App\Models\Branch;

class TerminalGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing merchants
        $merchants = Merchant::all();
        
        if ($merchants->isEmpty()) {
            $this->command->info('No merchants found. Please run MerchantSeeder first.');
            return;
        }

        // Get existing terminals
        $terminals = Terminal::all();
        
        if ($terminals->isEmpty()) {
            $this->command->info('No terminals found. Please run TerminalSeeder first.');
            return;
        }

        // Terminal group templates
        $terminalGroupTemplates = [
            [
                'name' => 'Main Floor Terminals',
                'description' => 'Terminals located on the main floor for general transactions',
            ],
            [
                'name' => 'Drive-Thru Terminals',
                'description' => 'Terminals dedicated to drive-thru operations',
            ],
            [
                'name' => 'Customer Service Terminals',
                'description' => 'Terminals for customer service and support',
            ],
            [
                'name' => 'Back Office Terminals',
                'description' => 'Terminals for administrative and back office tasks',
            ],
        ];

        foreach ($merchants as $merchant) {
            // Get branches for this merchant
            $merchantBranches = $merchant->branches;
            
            // If no branches exist for this merchant, create terminal groups without branch association
            if ($merchantBranches->isEmpty()) {
                foreach ($terminalGroupTemplates as $terminalGroupData) {
                    $terminalGroup = TerminalGroup::create([
                        'name' => $terminalGroupData['name'] . ' - ' . $merchant->name,
                        'group_id' => TerminalGroup::generateGroupId(),
                        'merchant_id' => $merchant->id,
                        'branch_id' => null, // No branch association
                        'description' => $terminalGroupData['description'],
                        'is_active' => true,
                    ]);

                    // Attach 2-4 random terminals to each group
                    $randomTerminals = $terminals->random(min(4, $terminals->count()));
                    $terminalGroup->terminals()->attach($randomTerminals->pluck('id'));
                }
            } else {
                // Create terminal groups for each branch
                foreach ($merchantBranches as $branch) {
                    foreach ($terminalGroupTemplates as $terminalGroupData) {
                        $terminalGroup = TerminalGroup::create([
                            'name' => $terminalGroupData['name'] . ' - ' . $branch->name,
                            'group_id' => TerminalGroup::generateGroupId(),
                            // 'merchant_id' => $merchant->id,
                            // 'branch_id' => $branch->id,
                            'description' => $terminalGroupData['description'],
                            'is_active' => true,
                        ]);

                        // Attach 2-4 random terminals to each group
                        $randomTerminals = $terminals->random(min(4, $terminals->count()));
                        $terminalGroup->terminals()->attach($randomTerminals->pluck('id'));
                    }
                }
            }
        }

        $this->command->info('Terminal Groups seeded successfully!');
    }
} 