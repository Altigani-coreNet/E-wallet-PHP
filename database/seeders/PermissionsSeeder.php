<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get permissions from config
        $webPermissions = config('permission.web_permissions');
        $merchantPermissions = config('permission.merchant_permissions', []);
        $adminPermissions = config('permission.admin_permissions', []);

        // Create admin permissions from dedicated admin_permissions config
        if (isset($adminPermissions['pos_permissions'])) {
            foreach ($adminPermissions['pos_permissions'] as $category => $perms) {
                foreach ($perms as $permName) {
                    Permission::firstOrCreate([
                        'name' => "pos.{$category}.{$permName}",
                        'guard_name' => 'admin'
                    ], [
                        'display_name' => ucwords(str_replace('_', ' ', $permName))
                    ]);
                }
            }
        }
        if (isset($adminPermissions['sales_permissions'])) {
            foreach ($adminPermissions['sales_permissions'] as $category => $perms) {
                foreach ($perms as $permName) {
                    Permission::firstOrCreate([
                        'name' => "sales.{$category}.{$permName}",
                        'guard_name' => 'admin'
                    ], [
                        'display_name' => ucwords(str_replace('_', ' ', $permName))
                    ]);
                }
            }
        }

        if (isset($adminPermissions['accounting_permissions'])) {
            foreach ($adminPermissions['accounting_permissions'] as $category => $perms) {
                foreach ($perms as $permName) {
                    Permission::firstOrCreate([
                        'name' => "accounting.{$category}.{$permName}",
                        'guard_name' => 'admin'
                    ], [
                        'display_name' => ucwords(str_replace('_', ' ', $permName))
                    ]);
                }
            }
        }

        // Create web permissions from config
        foreach ($webPermissions as $key => $permission) {
            Permission::firstOrCreate(['name' => $key, 'guard_name' => 'web']);
            foreach ($permission as $value) {
                Permission::firstOrCreate(['name' => $value, 'guard_name' => 'web']);
            }
        }

        // Create merchant permissions (POS + Sales modules)
        $merchantPermissions = $merchantPermissions;
        
        // Create POS permissions
        if (isset($merchantPermissions['pos_permissions'])) {
            foreach ($merchantPermissions['pos_permissions'] as $category => $perms) {
                foreach ($perms as $permName) {
                    Permission::firstOrCreate([
                        'name' => "pos.{$category}.{$permName}",
                        'guard_name' => 'web'
                    ], [
                        'display_name' => ucwords(str_replace('_', ' ', $permName))
                    ]);
                }
            }
        }

        // Create Sales permissions
        if (isset($merchantPermissions['sales_permissions'])) {
            foreach ($merchantPermissions['sales_permissions'] as $category => $perms) {
                foreach ($perms as $permName) {
                    Permission::firstOrCreate([
                        'name' => "sales.{$category}.{$permName}",
                        'guard_name' => 'web'
                    ], [
                        'display_name' => ucwords(str_replace('_', ' ', $permName))
                    ]);
                }
            }
        }

        // Get all admin permissions
        $permission = Permission::where("guard_name", "admin")->get()->pluck("id");
        $webPermission = Permission::where("guard_name", "web")->get()->pluck("id");
        
        // Create or get admin role and assign all admin permissions
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'admin']);
        $role->syncPermissions($permission);
        
        // Create or get web role and assign all web permissions
        $webRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'web', 'guard_name' => 'web']);
        $webRole->syncPermissions($webPermission);
        
        $this->command->info('Permissions seeded successfully!');
        $this->command->info('Total admin permissions: ' . Permission::where('guard_name', 'admin')->count());
        $this->command->info('Total web permissions: ' . Permission::where('guard_name', 'web')->count());
        $this->command->info('Total POS permissions: ' . Permission::where('name', 'like', 'pos.%')->count());
        $this->command->info('Total Sales permissions: ' . Permission::where('name', 'like', 'sales.%')->count());
    }
}

