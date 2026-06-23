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
        Schema::create('terminals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name'); // Terminal name
            $table->string('terminal_id')->unique();
            $table->uuid('merchant_id')->nullable(); // Merchant ID (references SoftPos)
            $table->uuid('branch_id')->nullable(); // Branch ID (references SoftPos)
            $table->string('brand')->nullable(); // Terminal brand
            $table->string('model')->nullable(); // Terminal model
            $table->string('manufacturer')->nullable(); // Terminal manufacturer
            $table->string('serial_no')->nullable(); // Serial number
            $table->string('sdk_id')->nullable(); // SDK ID
            $table->string('sdk_version')->nullable(); // SDK Version
            $table->string('android_os')->nullable(); // Android OS
            $table->enum('add_type', ['auto', 'static'])->default('static'); // Add type: auto (API registration) or static (manual)
            $table->boolean('is_active')->default(true); // Active status
            $table->enum('terminal_status', ['online', 'offline', 'testing'])->default('testing');
            $table->boolean('is_assigned_to_group')->default(false); // Whether terminal is assigned to a group
            $table->string('device_id')->nullable();
            $table->uuid('current_user_id')->nullable(); // Current user using this terminal
            $table->uuid('country_id')->nullable(); // Country ID
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['is_active']);
            $table->index('terminal_id');
            $table->index('merchant_id');
            $table->index('current_user_id');
            $table->index('device_id');
        });

        // Add foreign key constraint for current_user_id
        Schema::table('terminals', function (Blueprint $table) {
            $table->foreign('current_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('terminals', function (Blueprint $table) {
            $table->dropForeign(['current_user_id']);
        });
        
        Schema::dropIfExists('terminals');
    }
};

