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
        Schema::create('payment_by_links', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->uuid('merchant_id'); // Changed to UUID
            $table->string('status')->default('pending');
            $table->enum('payment_status', ['paid', 'unpaid'])->default('unpaid');
            $table->text('link');
            $table->string('payment_sdk');
            $table->decimal('amount', 15, 2);
            $table->uuid('currency_id'); // Changed to UUID for AuthService currency
            $table->string('currency_code')->nullable(); // Store currency code for quick access
            $table->json('currency_object')->nullable(); // Store full currency entity from AuthService
            $table->json('payment_method_types')->nullable();
            $table->dateTime('scheduled_date')->nullable();
            $table->dateTime('expired_date')->nullable();
            $table->uuid('customer_id')->nullable();
            
            // Customer information fields (storing directly instead of FK)
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_email')->nullable();
            
            $table->uuid('country_id')->nullable(); // Added country_id as UUID
            $table->json('metadata')->nullable(); // Added metadata field
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.  
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_by_links');
    }
};
