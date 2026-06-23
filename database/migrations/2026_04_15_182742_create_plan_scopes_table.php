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
        Schema::create('plan_scopes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('plans')->onDelete('cascade');
            $table->string('scope_type'); // e.g., 'users', 'products', 'customers', etc.
            $table->string('module')->nullable(); // e.g., 'pos', 'cashier', 'payments'
            $table->boolean('is_enabled')->default(false);
            $table->integer('max_count')->nullable(); // null means unlimited
            $table->timestamps();
            
            // Ensure one scope per plan per scope_type
            $table->unique(['plan_id', 'scope_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_scopes');
    }
};
