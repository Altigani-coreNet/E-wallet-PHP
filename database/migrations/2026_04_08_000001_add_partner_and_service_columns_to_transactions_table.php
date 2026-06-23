<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->uuid('partner_id')->nullable()->after('merchant_id');
            $table->uuid('service_category_id')->nullable()->after('partner_id');
            $table->uuid('service_id')->nullable()->after('service_category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['partner_id', 'service_category_id', 'service_id']);
        });
    }
};
