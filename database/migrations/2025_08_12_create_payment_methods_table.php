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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            
            // Payment method details
            $table->string('entry_mode')->nullable(); // NFC, CHIP, SWIPE, etc.
            $table->string('pan_token')->nullable(); // Tokenized card number
            $table->string('cardholder_name')->nullable(); // Cardholder name
            $table->integer('expiry_month')->nullable(); // Card expiry month
            $table->integer('expiry_year')->nullable(); // Card expiry year
            
            // Additional payment method fields
            $table->string('card_type')->nullable(); // VISA, MASTERCARD, etc.
            $table->string('card_brand')->nullable(); // Debit, Credit, etc.
            $table->string('issuer_bank')->nullable(); // Bank name
            
            // Security and verification
            $table->string('cvv_present')->nullable(); // Whether CVV was provided
            $table->string('pin_present')->nullable(); // Whether PIN was entered
            
            // Additional data
            $table->string('payment_channel')->nullable(); // e.g. Google Pay, Apple Pay, Samsung Pay, Digital Wallet
            $table->json('metadata')->nullable(); // Store additional payment method data
            
            // Timestamps
            $table->timestamps();
            
            // Indexes for better performance
            $table->index('entry_mode');
            $table->index('card_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
}; 