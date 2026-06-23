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
        Schema::create('transaction_logs', function (Blueprint $table) {
            $table->id();
            
            // Reference to the transaction
            $table->unsignedBigInteger('transaction_id');
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');
            
            // Log details
            $table->string('action'); // created, updated, cancelled, voided, refunded, partial_refunded, status_changed, etc.
            $table->string('action_type'); // system, user, admin, api, etc.
            $table->uuid('performed_by')->nullable(); // user_id or admin_id who performed the action
            $table->string('performed_by_type')->nullable(); // App\Models\User, App\Models\Admin, etc.
            
            // Amount changes (for refunds, partial refunds, etc.)
            $table->decimal('amount_before', 10, 2)->nullable();
            $table->decimal('amount_after', 10, 2)->nullable();
            $table->decimal('amount_change', 10, 2)->nullable(); // positive for refunds, negative for charges
            
            // Status changes
            $table->string('status_before')->nullable();
            $table->string('status_after')->nullable();
            
            // Additional data
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // Store any additional data like reason codes, notes, etc.
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Indexes for better performance
            $table->index('transaction_id');
            $table->index('action');
            $table->index('action_type');
            $table->index('performed_by');
            $table->index('created_at');
            $table->index(['transaction_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_logs');
    }
};
