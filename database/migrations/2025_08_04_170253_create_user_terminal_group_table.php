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
        Schema::create('user_terminal_group', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->uuid('terminal_group_id');
            $table->timestamps();
            
            // Foreign key constraints 
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('terminal_group_id')->references('id')->on('terminal_groups')->onDelete('cascade');
            
            // Unique constraint to prevent duplicate relationships
            $table->unique(['user_id', 'terminal_group_id']);
            
            // Indexes for better performance
            $table->index(['user_id', 'terminal_group_id']);
            $table->index('user_id');
            $table->index('terminal_group_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_terminal_group');
    }
};

