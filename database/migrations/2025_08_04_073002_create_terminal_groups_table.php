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
        Schema::create('terminal_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name'); // Group name
            $table->string('group_id')->unique(); // Unique group identifier
            $table->uuid('parent_id')->nullable(); // Foreign key to parent terminal group
            $table->uuid('merchant_id')->nullable(); // Merchant ID (references SoftPos)
            $table->uuid('branch_id')->nullable(); // Branch ID (references SoftPos)
            $table->text('description')->nullable(); // Optional description
            $table->boolean('is_active')->default(true); // Active status
            $table->unsignedBigInteger('country_id')->nullable(); // Country ID
            $table->timestamps();
            
            // Foreign key constraints 
            $table->foreign('parent_id')->references('id')->on('terminal_groups')->onDelete('set null');
            
            // Indexes for better performance
            $table->index(['parent_id', 'is_active']);
            $table->index('group_id');
            $table->index(['merchant_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('terminal_groups');
    }
};

