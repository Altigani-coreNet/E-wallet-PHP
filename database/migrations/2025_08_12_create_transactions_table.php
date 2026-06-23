<?php

use App\Models\Transaction;
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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            
            // Core Transaction Details (from both tables)
            $table->decimal('amount', 10, 2)->nullable();
            $table->uuid('currency_id')->nullable(); // UUID from external service
            $table->string('currency_symbol')->nullable(); // Store currency symbol for quick access
            $table->json('currency_object')->nullable(); // Store full currency entity from AuthService
            $table->string('transaction_id')->unique();
            $table->timestamp('timestamp')->nullable();
            $table->string('transaction_type')->nullable(); // SALE, REFUND, VOID, etc.
            
            // Payment Processing Details (from transactions table)
            $table->string('batch_no')->nullable();
            $table->string('trace_no')->nullable();
            $table->string('rrn')->nullable(); // Retrieval Reference Number
            $table->string('auth_code')->nullable(); // Approval code
            $table->string('mid')->nullable(); // Merchant ID from SDK
            $table->string('tid')->nullable(); // Terminal ID from SDK
            $table->string('state')->nullable();
            $table->text('description')->nullable();
            $table->dateTime('transaction_datetime')->nullable();
            $table->string('invoice_no')->nullable();
            $table->string('card_number')->nullable(); // Masked card number
            $table->string('expiry')->nullable(); // Expiry date
            $table->string('method')->nullable(); // e.g. VISA
            $table->string('ref_no')->nullable(); // Same as RRN
            $table->string('atc')->nullable(); // Application Transaction Counter
            $table->string('tvr')->nullable(); // Terminal Verification Results
            $table->string('app_name')->nullable(); // e.g. Visa Debit
            $table->string('tsi')->nullable(); // Transaction Status Information
            $table->string('sdk')->nullable(); // SDK information
            
            // Payment Method (from pos_transactions table)
            $table->unsignedBigInteger('payment_method_id')->nullable();
            
            $table->enum('payment_type', Transaction::PAYMENT_TYPES)->nullable();
            // Merchant and Terminal Details (combined from both tables)
            $table->uuid('merchant_id')->nullable(); // Changed to UUID
            $table->uuid('user_id')->nullable(); // Foreign key to users table
            $table->uuid('terminal_id')->nullable(); // UUID from AuthService
            
            // Security Data (from pos_transactions table)
            $table->text('emv_data')->nullable();
            $table->string('pin_block')->nullable();
            $table->string('ksn')->nullable();
            $table->uuid ('country_id')->nullable();
            
            // Transaction Status (from pos_transactions table)
            $table->enum('status', Transaction::TRANSACTION_STATUS)->default('pending'); // PENDING, APPROVED, DECLINED, FAILED
            $table->text('response_data')->nullable(); // Store API response
            $table->timestamp('processed_at')->nullable();
            
            // Additional fields (from pos_transactions table)
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // Store any additional data
            
            // Timestamps
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('payment_method_id')->references('id')->on('payment_methods')->onDelete('set null');
            // $table->foreign('terminal_id')->references('id')->on('terminals')->onDelete('set null'); // Removed - terminal_id is now UUID from AuthService
            // $table->foreign('merchant_id')->references('id')->on('merchants')->onDelete('set null'); // Removed - merchant_id is now UUID
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            // $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('set null'); // Removed - currency_id is now UUID
            // Indexes for better performance
            // $table->uuid('country_id')->nullable();
            $table->index('transaction_id');
            $table->index(['terminal_id', 'transaction_datetime']);
            $table->index(['merchant_id', 'transaction_datetime']);
            $table->index(['user_id', 'transaction_datetime']);
            $table->index('transaction_datetime');
            $table->index('status');
            $table->index('timestamp');
            
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
}; 