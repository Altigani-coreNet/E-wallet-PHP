<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->uuid('customer_id')->nullable();
            $table->string('scope');
            $table->string('idempotency_key');
            $table->string('status')->default('completed');
            $table->unsignedSmallInteger('response_code')->default(200);
            $table->json('response');
            $table->timestamps();

            $table->index('customer_id');
            $table->unique(['customer_id', 'scope', 'idempotency_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_idempotency_keys');
    }
};
