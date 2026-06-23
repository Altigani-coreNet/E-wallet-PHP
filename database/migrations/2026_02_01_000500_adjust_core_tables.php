<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Target tables to ensure nullable country_id FK to countries
        $tables = [
            'admins',
            'settlements',
            'batches',
            'transactions',
            'payment_by_links',
            'user_groups',
            'terminals',
            'terminal_groups',
           
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'country_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $table->uuid('country_id')->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'admins',
            'settlements',
            'batches',
            'transactions',
            'payment_by_links',
            'user_groups',
            'terminals',
            'terminal_groups',
            'branches',
            'customers',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'country_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $table->dropColumn('country_id');
                });
            }
        }
    }
};


