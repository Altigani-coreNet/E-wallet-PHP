<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');

        Schema::create('wallets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('user_number')->nullable();
            $table->string('wallet_id')->unique();
            $table->uuid('customer_id')->nullable();
            $table->uuid('merchant_id')->nullable();
            $table->uuid('currency_id')->nullable();
            $table->string('currency_code')->default('SDG');
            $table->decimal('balance', 15, 2)->default('0.00');
            $table->decimal('available_balance', 15, 2)->default('0.00');
            $table->unsignedBigInteger('account_id')->nullable();
            $table->enum('status', ['active', 'frozen', 'closed'])->default('active');
            $table->timestamps();

            $table->index('user_number');
            $table->index('customer_id');
            $table->index('merchant_id');
            $table->index('account_id');
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('wallet_id');
            $table->enum('type', ['topup', 'payment', 'transfer', 'refund', 'adjustment']);
            $table->enum('direction', ['credit', 'debit']);
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_after', 15, 2)->default('0.00');
            $table->string('reference')->nullable();
            $table->string('reference_id', 36)->nullable();
            $table->text('description')->nullable();
            $table->integer('created_by')->default(0);
            $table->timestamps();

            $table->foreign('wallet_id')->references('id')->on('wallets')->onDelete('cascade');
            $table->index('wallet_id');
            $table->index(['reference', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');

        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->uuid('customer_id')->nullable();
            $table->uuid('merchant_id')->nullable();
            $table->uuid('currency_id')->nullable();
            $table->string('currency_code')->default('SDG');
            $table->decimal('balance', 15, 2)->default('0.00');
            $table->decimal('available_balance', 15, 2)->default('0.00');
            $table->unsignedBigInteger('account_id')->nullable();
            $table->enum('status', ['active', 'frozen', 'closed'])->default('active');
            $table->timestamps();

            $table->index('customer_id');
            $table->index('merchant_id');
            $table->index('account_id');
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wallet_id');
            $table->enum('type', ['topup', 'payment', 'transfer', 'refund', 'adjustment']);
            $table->enum('direction', ['credit', 'debit']);
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_after', 15, 2)->default('0.00');
            $table->string('reference')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('description')->nullable();
            $table->integer('created_by')->default(0);
            $table->timestamps();

            $table->index('wallet_id');
            $table->index(['reference', 'reference_id']);
        });
    }
};
