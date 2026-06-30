<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transfer_otps', function (Blueprint $table) {
            $table->id();
            $table->uuid('customer_id');
            $table->string('token', 64)->unique();
            $table->unsignedInteger('code');
            $table->json('payload');
            $table->dateTime('expires_at');
            $table->dateTime('consumed_at')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'consumed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transfer_otps');
    }
};
