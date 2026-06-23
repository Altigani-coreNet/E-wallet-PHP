<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('password')->nullable()->after('phone');
            $table->decimal('balance', 20, 2)->default(0)->after('password');
            $table->dateTime('birth_date')->nullable()->after('balance');
            $table->string('gender')->nullable()->after('birth_date');
            $table->boolean('profile_completed')->default(false)->after('merchant_id');
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE customers MODIFY name VARCHAR(255) NULL');
            DB::statement('ALTER TABLE customers MODIFY email VARCHAR(255) NULL');
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->unique('phone');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['phone']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'password',
                'balance',
                'birth_date',
                'gender',
                'profile_completed',
            ]);
        });
    }
};
