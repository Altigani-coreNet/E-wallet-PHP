<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\UserGroup;
use App\Models\Merchant;
use App\Models\User;
use App\Models\Terminal;
use App\Models\TerminalGroup;

class UserGroupSeeder extends Seeder
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

        // Create exactly 4 user groups for each merchant
        $userGroupTemplates = [
            [
                'name' => 'Cashiers Group',
                'description' => 'Group for cashier users who handle transactions',
                'is_single_terminal' => false,
            ],
            [
                'name' => 'Managers Group',
                'description' => 'Group for manager users with administrative access',
                'is_single_terminal' => false,
            ],
            [
                'name' => 'Single Terminal Group',
                'description' => 'Group for users assigned to a specific terminal',
                'is_single_terminal' => true,
            ],
            [
                'name' => 'Supervisors Group',
                'description' => 'Group for supervisor users with oversight capabilities',
                'is_single_terminal' => false,
            ],
        ];

        foreach ($merchants as $merchant) {
            foreach ($userGroupTemplates as $userGroupData) {
                $userGroup = UserGroup::create([
                    'name' => $userGroupData['name'] . ' - ' . $merchant->name,
                    'group_id' => UserGroup::generateGroupId(),
                    'merchant_id' => $merchant->id,
                    'branch_id' => $merchant->branches->random()->id,	
                    'description' => $userGroupData['description'],
                    'is_active' => true,
                    'is_single_terminal' => $userGroupData['is_single_terminal'],
                    'terminal_id' => null,
                ]);

                // Get users for this merchant
                $merchantUsers = User::where('merchant_id', $merchant->id)->get();
                
                if ($merchantUsers->isNotEmpty()) {
                    // Attach 2-3 random users to each group
                    $randomUsers = $merchantUsers->random(min(3, $merchantUsers->count()));
                    $userGroup->users()->attach($randomUsers->pluck('id'));
                }

                if ($userGroupData['is_single_terminal']) {
                    // For single terminal mode, assign a random terminal
                    // $merchantTerminals = Terminal::where('merchant_id', $merchant->id)->get();
                    // if ($merchantTerminals->isNotEmpty()) {
                    //     $userGroup->update([
                    //         'terminal_id' => $merchantTerminals->random()->id
                    //     ]);
                    // }
                } else {
                    // For multiple terminal groups mode, assign random terminal groups
                    $merchantTerminalGroups = TerminalGroup::where('merchant_id', $merchant->id)->get();
                    if ($merchantTerminalGroups->isNotEmpty()) {
                        $randomTerminalGroups = $merchantTerminalGroups->random(min(2, $merchantTerminalGroups->count()));
                        $userGroup->terminalGroups()->attach($randomTerminalGroups->pluck('id'));
                    }
                }
            }
        }

        $this->command->info('User Groups seeded successfully!');
    }
} 