<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('customers', 'status')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->string('status', 20)->default('pending')->after('profile_completed');
            });

            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('customers', 'status')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->change();
        });
    }
};
