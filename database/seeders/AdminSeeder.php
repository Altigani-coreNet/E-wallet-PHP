<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = Admin::create([
            'email' => 'admin@corenet-tech.com',
            "password" => bcrypt('12345678'),
            "name" => 'Corenet Tech Admin',
            "phone" => "052420102",
            "status" => 'active',
        ]);

        $admin->assignRole('admin');
        
        $this->command->info('Admin user created successfully!');
        $this->command->info('Email: admin@corenet-tech.com | Password: 12345678');
    }
}

