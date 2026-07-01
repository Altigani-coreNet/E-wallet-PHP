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
            $table->timestamp('email_verified_at')->nullable()->after('email');
            $table->timestamp('phone_verified_at')->nullable()->after('phone');
        });

        DB::table('customers')
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->whereNull('phone_verified_at')
            ->update(['phone_verified_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['email_verified_at', 'phone_verified_at']);
        });
    }
};
