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
        Schema::create('user_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name'); // Group name
            $table->string('group_id')->unique(); // Unique group identifier
            $table->uuid('merchant_id'); // Foreign key to merchants table
            $table->uuid('branch_id')->nullable(); // Foreign key to branches table
            $table->uuid('country_id')->nullable(); // Country ID
            $table->text('description')->nullable(); // Optional description
            $table->boolean('is_active')->default(true); // Active status
            $table->timestamps();
            
            // Foreign key constraints 
            $table->foreign('merchant_id')->references('id')->on('merchants')->onDelete('cascade');
            // $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            
            // Indexes for better performance
            $table->index(['merchant_id', 'is_active']);
            $table->index(['merchant_id', 'branch_id']);
            $table->index('group_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_groups');
    }
}; 