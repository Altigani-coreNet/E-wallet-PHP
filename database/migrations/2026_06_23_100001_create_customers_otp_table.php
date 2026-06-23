<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers_otp', function (Blueprint $table) {
            $table->id();
            $table->string('identifier');
            $table->enum('channel', ['email', 'sms']);
            $table->unsignedInteger('code');
            $table->string('token')->unique();
            $table->boolean('is_verified')->default(false);
            $table->dateTime('expires_at');
            $table->timestamps();

            $table->index('token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers_otp');
    }
};
