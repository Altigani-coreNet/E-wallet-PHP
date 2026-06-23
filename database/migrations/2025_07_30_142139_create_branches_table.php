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
        Schema::create('branches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name'); // Branch name
            $table->text('address')->nullable(); // Branch address
            $table->boolean('is_active')->default(true); // Active status
            $table->enum('status', ['pending', 'approved', 'rejected', 'suspended', 'viewed', 'deleted'])->default('pending'); // Branch approval status
            $table->uuid('merchant_id')->nullable(); // Foreign key to merchants table
            $table->timestamps();
            
            // Foreign key constraint
            $table->foreign('merchant_id')->references('id')->on('merchants')->onDelete('cascade');
            
            // Index for better performance
            $table->index(['merchant_id', 'is_active', 'status']);

            $table->uuid('country_id')->nullable();
            
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
}; 