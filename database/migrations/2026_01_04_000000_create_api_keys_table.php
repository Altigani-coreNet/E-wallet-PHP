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
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->uuid('merchant_id'); // UUID from AuthService
            $table->string('mode')->default('test'); // test or live
            $table->string('public_key')->unique();
            $table->string('secret_key');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            
            // Index for faster lookups
            $table->index(['merchant_id', 'mode']);
            $table->index('public_key');
            
            // No foreign key constraint - merchant_id comes from AuthService
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};

