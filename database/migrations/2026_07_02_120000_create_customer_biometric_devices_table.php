<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_biometric_devices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('customer_id');
            $table->string('device_id', 64);
            $table->string('device_name')->nullable();
            $table->enum('platform', ['ios', 'android']);
            $table->text('public_key');
            $table->string('algorithm', 16)->default('ES256');
            $table->enum('status', ['active', 'revoked'])->default('active');
            $table->timestamp('enrolled_at');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->unique(['customer_id', 'device_id']);
            $table->index(['device_id', 'status']);
            $table->index(['customer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_biometric_devices');
    }
};
