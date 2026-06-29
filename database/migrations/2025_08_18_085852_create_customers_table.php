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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->unique();
            $table->string('password')->nullable();
            $table->decimal('balance', 20, 2)->default(0);
            $table->dateTime('birth_date')->nullable();
            $table->string('gender')->nullable();
            $table->string('national_id', 50)->nullable()->unique();
            $table->string('profile_image')->nullable();
            $table->string('address')->nullable();
            $table->uuid('country_id')->nullable();
            $table->uuid('merchant_country_id')->nullable();
            $table->uuid('city_id')->nullable();
            $table->string('state')->nullable();
            $table->string('zip')->nullable();
            $table->uuid('merchant_id')->nullable();
            $table->boolean('profile_completed')->default(false);
            $table->string('status', 20)->default('pending');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
