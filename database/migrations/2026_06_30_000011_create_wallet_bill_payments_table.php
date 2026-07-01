<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_bill_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('wallet_id');
            $table->uuid('customer_id');
            $table->uuid('partner_id');
            $table->uuid('service_id');
            $table->uuid('product_id')->nullable();
            $table->decimal('amount', 15, 2);
            $table->decimal('fee', 15, 2)->default('0.00');
            $table->decimal('total_debited', 15, 2);
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->string('partner_reference')->nullable();
            $table->json('service_payload')->nullable();
            $table->string('service_url')->nullable();
            $table->json('partner_response')->nullable();
            $table->text('error_message')->nullable();
            $table->string('ledger_reference')->nullable();
            $table->string('ledger_reference_id', 36)->nullable();
            $table->string('idempotency_key')->nullable();
            $table->timestamps();

            $table->foreign('wallet_id')->references('id')->on('wallets')->cascadeOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->foreign('partner_id')->references('id')->on('partners')->cascadeOnDelete();
            $table->foreign('service_id')->references('id')->on('services')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();

            $table->index(['wallet_id', 'created_at']);
            $table->index(['partner_id', 'status']);
            $table->index('idempotency_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_bill_payments');
    }
};
