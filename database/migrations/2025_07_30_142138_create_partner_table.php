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
        Schema::create('partners', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Unique merchant ID
            $table->string('name'); // Merchant business name
            $table->string('owner_name')->nullable(); // Owner's name
            $table->string('email')->unique(); // Email for login or contact
            $table->string('phone')->nullable(); // Contact phone number
            $table->text('address')->nullable(); // Business address
            $table->string('business_type')->nullable(); // e.g. retail, food, etc.
            $table->boolean('is_active')->default(true); // Active status
            $table->string('logo')->nullable(); // Logo path or URL
            $table->string('merchant_code')->unique(); // Custom merchant ID/code
            $table->uuid('user_id')->nullable(); // If tied to a user account
            $table->uuid('country_id')->nullable(); // If tied to a user account
            $table->string('status')->default('pending');
            $table->string('latitude')->nullable();
            $table->uuid('currency')->nullable();
            // $table->foreignId('currency')->references('id')->on('currencies')->onDelete('set null');
            $table->string('longitude')->nullable();
            $table->string('add_type')->default('admin_dashboard');
            $table->uuid('partner_category_id')->nullable();
            // Type of merchant
            // $table->string('ówner_phone')->nullable();
            // Status of the merchant
            $table->uuid('partner_id')->nullable();
            $table->timestamps();
            
            // Foreign key constraints (added conditionally to avoid errors if tables don't exist yet)
            // if (Schema::hasTable('users')) {
            //     $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            // }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};
