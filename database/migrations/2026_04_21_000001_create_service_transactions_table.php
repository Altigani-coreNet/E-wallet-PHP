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
        Schema::create('service_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id');
            $table->uuid('merchant_id')->nullable();
            $table->uuid('partner_id')->nullable();
            $table->uuid('service_id')->nullable();
            $table->uuid('product_id')->nullable();
            $table->string('status')->default('pending');
            $table->string('service_url')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('service_response')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');
            $table->index(['merchant_id', 'created_at']);
            $table->index(['service_id', 'created_at']);
            $table->index(['product_id', 'created_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_transactions');
    }
};

