<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // جدول الـBatch
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_number')->unique();
            $table->uuid('merchant_id'); // Changed to UUID
            $table->uuid('user_id')->nullable();
            $table->enum('status', ['pending', 'settled', 'failed'])->default('pending');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('total_amount_refunded', 15, 2)->default(0);
            $table->decimal('total_amount_voided', 15, 2)->default(0);
            // $table->string('currency')->default('AED');
            $table->uuid('currency_id')->nullable();
            $table->string('currency_symbol')->nullable(); // Store currency symbol for quick access
            // $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('set null');
            $table->integer('transaction_count')->default(0);
            $table->timestamps();
            $table->timestamp('settled_at')->nullable();
            
            // Foreign key constraint for merchant_id
            // $table->foreign('merchant_id')->references('id')->on('merchants')->onDelete('cascade'); // Removed - merchant_id is now UUID
            // Foreign key constraint for user_id
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        // إضافة حقل batch_id إلى جدول المعاملات
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('batch_id')->nullable()->after('id');
            $table->foreign('batch_id')->references('id')->on('batches')->onDelete('set null');
        });
    }

    public function down(): void
    {
        // إزالة العلاقة أولا
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['batch_id']);
            $table->dropColumn('batch_id');
        });

        // حذف جدول الـBatch
        Schema::dropIfExists('batches');
    }
};
