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
        Schema::create('user_group_user', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_group_id');
            $table->uuid('user_id');
            $table->timestamps();
            
            // Foreign key constraints 
            // $table->foreign('user_group_id')->references('id')->on('user_groups')->onDelete('cascade');
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Unique constraint to prevent duplicate relationships
            $table->unique(['user_group_id', 'user_id']);
            
            // Indexes for better performance
            $table->index(['user_group_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_group_user');
    }
}; 