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
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->string('address')->nullable();
            $table->uuid('country_id')->nullable();
            $table->uuid('merchant_country_id')->nullable();
            $table->uuid('city_id')->nullable();
            $table->string('state')->nullable();
            $table->string('zip')->nullable();
            $table->uuid('merchant_id')->nullable();
            // $table->foreignId('merchant_id')->constrained('merchants');
            $table->timestamps();
        });

        // Schema::table('customers', function (Blueprint $table) {
        //     $table->foreign('country_id')->references('id')->on('countries')->onDelete('set null');
        //     $table->foreign('city_id')->references('id')->on('cities')->onDelete('set null');
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
