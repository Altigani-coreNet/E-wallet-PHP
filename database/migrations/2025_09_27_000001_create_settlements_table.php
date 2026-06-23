<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // جدول الـSettlements
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->string('settlement_number')->unique();
            $table->unsignedBigInteger('batch_id'); // Foreign key to batches table
            $table->uuid('merchant_id'); // Changed to UUID
            $table->uuid('user_id')->nullable();
            $table->enum('status', ['pending', 'settled', 'failed'])->default('pending');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->integer('transaction_count')->default(0);
            $table->uuid('currency_id')->nullable();
            $table->string('currency_symbol')->nullable(); // Store currency symbol for quick access
            // $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('set null');
            $table->timestamps();
            $table->timestamp('settled_at')->nullable();
            
            // Foreign key constraints
            // $table->foreign('batch_id')->references('id')->on('batches')->onDelete('cascade');
            // $table->foreign('merchant_id')->references('id')->on('merchants')->onDelete('cascade');
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        // حذف جدول الـSettlements
        Schema::dropIfExists('settlements');
    }
};
