<?php

namespace Database\Seeders;

use App\Enums\BusinessType;
use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Creates demo merchants that are missing from an older/partial MerchantSeeder run
 * (electronics@corenet.com, restaurant@corenet.com).
 */
class MissingDemoMerchantsSeeder extends Seeder
{
    public function run(): void
    {
        $permissionNames = $this->merchantPermissionNames();
        $permissions = Permission::whereIn('name', $permissionNames)->where('guard_name', 'web')->get();

        $country = Country::query()->where('short_name', 'SD')->orWhere('code', '249')->first()
            ?? Country::query()->first();
        $city = City::query()->where('country_id', $country?->id)->first();
        $currency = Currency::query()->first();
        $plan = Plan::query()->where('name', 'Standard')->first();

        $merchants = [
            [
                'name' => 'Gadget World',
                'owner_name' => 'Chris Taylor',
                'email' => 'electronics@corenet.com',
                'phone' => '+249912345683',
                'address' => '303 Electronics Market, Kassala',
                'business_name' => 'Gadget World',
                'business_type' => BusinessType::ELECTRONICS->value,
            ],
            [
                'name' => 'Fresh Bites Restaurant',
                'owner_name' => 'James Wilson',
                'email' => 'restaurant@corenet.com',
                'phone' => '+249912345685',
                'address' => '505 Nile Street, Khartoum',
                'business_name' => 'Fresh Bites Restaurant',
                'business_type' => BusinessType::RESTAURANT->value,
            ],
        ];

        foreach ($merchants as $row) {
            $this->upsertDemoMerchant($row, $country?->id, $city?->id, $currency?->id, $plan?->id, $permissions);
            $this->command->info("Upserted demo merchant: {$row['email']}");
        }
    }

    /**
     * @param \Illuminate\Support\Collection<int, Permission> $permissions
     */
    private function upsertDemoMerchant(
        array $row,
        ?string $countryId,
        ?string $cityId,
        ?string $currencyId,
        ?string $planId,
        $permissions
    ): void {
        $user = User::withoutGlobalScopes()->updateOrCreate(
            ['email' => $row['email']],
            [
                'name' => explode(' ', $row['owner_name'])[0],
                'last_name' => explode(' ', $row['owner_name'])[1] ?? '',
                'phone' => $row['phone'],
                'password' => Hash::make('password123'),
                'status' => true,
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );

        $merchant = Merchant::withoutGlobalScopes()->updateOrCreate(
            ['email' => $row['email']],
            [
                'name' => $row['name'],
                'owner_name' => $row['owner_name'],
                'phone' => $row['phone'],
                'address' => $row['address'],
                'business_name' => $row['business_name'],
                'business_type' => $row['business_type'],
                'country_id' => $countryId,
                'city_id' => $cityId,
                'currency' => $currencyId,
                'is_active' => true,
                'status' => 'approved',
                'user_id' => $user->id,
                'merchant_code' => Merchant::withoutGlobalScopes()
                    ->where('email', $row['email'])
                    ->value('merchant_code') ?? Merchant::generateMerchantCode(),
                'scopes' => ['cashier', 'softpos'],
                'plan_id' => $planId,
            ]
        );

        $user->update(['merchant_id' => $merchant->id]);

        $role = Role::firstOrCreate(
            ['name' => 'merchant '.$merchant->id, 'guard_name' => 'web'],
            ['merchant_id' => $merchant->id]
        );
        $role->syncPermissions($permissions);
        if (! $user->hasRole($role->name)) {
            $user->assignRole($role);
        }
    }

    /** @return list<string> */
    private function merchantPermissionNames(): array
    {
        $merchantPermissions = config('permission.merchant_permissions', []);
        $names = [];
        foreach ($merchantPermissions['pos_permissions'] ?? [] as $category => $perms) {
            foreach ($perms as $permName) {
                $names[] = "pos.{$category}.{$permName}";
            }
        }
        foreach ($merchantPermissions['sales_permissions'] ?? [] as $category => $perms) {
            foreach ($perms as $permName) {
                $names[] = "sales.{$category}.{$permName}";
            }
        }

        return $names;
    }
}
