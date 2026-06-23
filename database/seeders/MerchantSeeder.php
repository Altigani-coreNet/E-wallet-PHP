<?php

namespace Database\Seeders;

use App\Models\Merchant;
use App\Models\User;
use App\Enums\BusinessType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class MerchantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Build the same POS/Sales permission names created in PermissionsSeeder
        $merchantPermissions = config('permission.merchant_permissions', []);
        $permissionNames = [];
        // POS permissions
        if (isset($merchantPermissions['pos_permissions'])) {
            foreach ($merchantPermissions['pos_permissions'] as $category => $perms) {
                foreach ($perms as $permName) {
                    $permissionNames[] = "pos.{$category}.{$permName}";
                }
            }
        }
        // Sales permissions
        if (isset($merchantPermissions['sales_permissions'])) {
            foreach ($merchantPermissions['sales_permissions'] as $category => $perms) {
                foreach ($perms as $permName) {
                    $permissionNames[] = "sales.{$category}.{$permName}";
                }
            }
        }
        // Resolve permission models
        $permissions = Permission::whereIn('name', $permissionNames)
            ->where('guard_name', 'web')
            ->get();

        $sudanCountry = \App\Models\Country::query()
            ->where('short_name', 'SD')
            ->orWhere('code', '249')
            ->first();

        if (! $sudanCountry) {
            $this->command->error('Sudan not found in countries. Run CountrySeeder before MerchantSeeder.');

            return;
        }

        // al-Khartum (cities.json id 3358 → cities.old_id)
        $khartoumCity = \App\Models\City::query()
            ->where('country_id', $sudanCountry->id)
            ->where('old_id', '3358')
            ->first()
            ?? \App\Models\City::query()->where('country_id', $sudanCountry->id)->first();

        // Kassala (cities.json id 3341)
        $kassalaCity = \App\Models\City::query()
            ->where('country_id', $sudanCountry->id)
            ->where('old_id', '3341')
            ->first()
            ?? \App\Models\City::query()
                ->where('country_id', $sudanCountry->id)
                ->where('id', '!=', $khartoumCity?->id)
                ->first()
            ?? $khartoumCity;

        $merchantCurrency = \App\Models\Currency::query()
            ->where('currency_code->en', 'SDG')
            ->first()
            ?? \App\Models\Currency::query()->find($sudanCountry->currency_id)
            ?? \App\Models\Currency::query()->first();

        // Get the Standard plan
        $standardPlan = \App\Models\Plan::where('name', 'Standard')->first();

        // Create 8 merchants with specific data
        $merchants = [
            [
                'name' => 'Tech Solutions Inc.',
                'owner_name' => 'John Smith',
                'email' => 'john@techsolutions.com',
                'phone' => '+249912345678',
                'address' => '123 Technology St, East Nile, Khartoum',
                'business_name' => 'Tech Solutions Inc.',
                'business_type' => BusinessType::ELECTRONICS->value,
                'country_id' => $sudanCountry->id,
                'city_id' => $khartoumCity?->id,
                'currency' => $merchantCurrency?->id,
                'is_active' => true,
                'status' => 'approved',
            ],
            [
                'name' => 'Fresh Foods Market',
                'owner_name' => 'Sarah Johnson',
                'email' => 'retail@corenet.com',
                'phone' => '+249912345679',
                'address' => '456 Souq Street, Khartoum',
                'business_name' => 'Fresh Foods Market',
                'business_type' => BusinessType::RETAIL->value,
                'country_id' => $sudanCountry->id,
                'city_id' => $khartoumCity?->id,
                'currency' => $merchantCurrency?->id,
                'is_active' => true,
                'status' => 'approved',
            ],
            [
                'name' => 'Fashion Forward',
                'owner_name' => 'Michael Brown',
                'email' => 'fashion@corenet.com',
                'phone' => '+249912345680',
                'address' => '789 Africa Rd, Kassala',
                'business_name' => 'Fashion Forward',
                'business_type' => BusinessType::RETAIL->value,
                'country_id' => $sudanCountry->id,
                'city_id' => $kassalaCity?->id,
                'currency' => $merchantCurrency?->id,
                'is_active' => true,
                'status' => 'approved',
            ],
            [
                'name' => 'Green Pharmacy',
                'owner_name' => 'Emily Davis',
                'email' => 'pharmasy@corenet.com',
                'phone' => '+249912345681',
                'address' => '101 Hospital St, Khartoum',
                'business_name' => 'Green Pharmacy',
                'business_type' => BusinessType::PHARMACY->value,
                'country_id' => $sudanCountry->id,
                'city_id' => $khartoumCity?->id,
                'currency' => $merchantCurrency?->id,
                'is_active' => true,
                'status' => 'approved',
            ],
            [
                'name' => 'ServicePro Solutions',
                'owner_name' => 'David Wilson',
                'email' => 'services@corenet.com',
                'phone' => '+249912345682',
                'address' => '202 Services Ave, Khartoum',
                'business_name' => 'ServicePro Solutions',
                'business_type' => BusinessType::SERVICES->value,
                'country_id' => $sudanCountry->id,
                'city_id' => $khartoumCity?->id,
                'currency' => $merchantCurrency?->id,
                'is_active' => true,
                'status' => 'approved',
            ],
            [
                'name' => 'Gadget World',
                'owner_name' => 'Chris Taylor',
                'email' => 'electronics@corenet.com',
                'phone' => '+249912345683',
                'address' => '303 Electronics Market, Kassala',
                'business_name' => 'Gadget World',
                'business_type' => BusinessType::ELECTRONICS->value,
                'country_id' => $sudanCountry->id,
                'city_id' => $kassalaCity?->id,
                'currency' => $merchantCurrency?->id,
                'is_active' => true,
                'status' => 'approved',
            ],
            [
                'name' => 'City Retail Hub',
                'owner_name' => 'Olivia Martinez',
                'email' => 'cityhub@corenet.com',
                'phone' => '+249912345684',
                'address' => '404 Central Plaza, Khartoum',
                'business_name' => 'City Retail Hub',
                'business_type' => BusinessType::RETAIL->value,
                'country_id' => $sudanCountry->id,
                'city_id' => $khartoumCity?->id,
                'currency' => $merchantCurrency?->id,
                'is_active' => true,
                'status' => 'approved',
            ],
            [
                'name' => 'Fresh Bites Restaurant',
                'owner_name' => 'James Wilson',
                'email' => 'restaurant@corenet.com',
                'phone' => '+249912345685',
                'address' => '505 Nile Street, Khartoum',
                'business_name' => 'Fresh Bites Restaurant',
                'business_type' => BusinessType::RESTAURANT->value,
                'country_id' => $sudanCountry->id,
                'city_id' => $khartoumCity?->id,
                'currency' => $merchantCurrency?->id,
                'is_active' => true,
                'status' => 'approved',
            ],
        ];

        foreach ($merchants as $merchantData) {
            // Create user for each merchant owner
            $user = User::create([
                'name' => explode(' ', $merchantData['owner_name'])[0], // First name
                'last_name' => explode(' ', $merchantData['owner_name'])[1] ?? '', // Last name
                'email' => $merchantData['email'],
                'phone' => $merchantData['phone'],
                'password' => Hash::make('password123'),
                'status' => true,
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]);

            // Create merchant with user association
            $merchant = Merchant::create([
                ...$merchantData,
                'user_id' => $user->id,
                'merchant_code' => Merchant::generateMerchantCode(),
                'scopes' => ['cashier', 'softpos'], // Default scopes for all seeded merchants
                'plan_id' => $standardPlan?->id, // Assign Standard plan to all merchants
            ]);

            // Update the user with the merchant_id
            $user->update([
                'merchant_id' => $merchant->id,
            ]);

            // Create merchant-specific role with merchant_id
            $merchantRole = Role::create([
                'name' => 'merchant ' . $merchant->id,
                'guard_name' => 'web',
                'merchant_id' => $merchant->id,
            ]);

            // Assign all web permissions to merchant role
            $merchantRole->syncPermissions($permissions);

            // Assign merchant role to the user
            $user->assignRole($merchantRole);
        }

        $this->command->info('8 Merchants created successfully!');
        $this->command->info('Merchant owners can login with password: password123');
    }
}

