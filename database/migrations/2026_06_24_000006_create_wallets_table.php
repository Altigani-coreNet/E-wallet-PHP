<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('user_number')->nullable();
            $table->string('wallet_id')->unique();
            $table->unsignedBigInteger('customer_id')->nullable();
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
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
