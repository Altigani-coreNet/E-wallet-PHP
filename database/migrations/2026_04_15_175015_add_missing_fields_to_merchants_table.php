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
        Schema::table('merchants', function (Blueprint $table) {
            // Business Information
            $table->string('business_name')->nullable()->after('name');
            $table->string('business_phone')->nullable()->after('phone');
            
            // Location Information
            $table->uuid('country_id')->nullable()->after('address');
            $table->uuid('city_id')->nullable()->after('country_id');
            $table->json('scopes')->nullable()->after('add_type');
            
            // Trade License Information
            $table->string('trade_license_number')->nullable()->after('business_type');
            $table->date('trade_license_start_date')->nullable()->after('trade_license_number');
            $table->date('trade_license_expired_date')->nullable()->after('trade_license_start_date');
            
            // Tax Information
            $table->string('tax_number')->nullable()->after('trade_license_expired_date');
            $table->foreignId('plan_id')->nullable()->after('currency')->constrained('plans')->onDelete('set null');
            
            // Foreign key constraints
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('set null');
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            // Drop foreign key constraints first
            $table->dropForeign(['country_id']);
            $table->dropForeign(['city_id']);
            
            // Drop columns
            $table->dropColumn([
                'business_name',
                'country_id',
                'city_id',
                'trade_license_number',
                    'trade_license_start_date',
                    'trade_license_expired_date',
                'tax_number',
                'scopes',
                
            ]);
        });
    }
};
