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
        Schema::create('terminal_group_user_group', function (Blueprint $table) {
            $table->uuid('terminal_group_id');
            $table->uuid('user_group_id');
            $table->timestamps();

            // Foreign keys
            $table->foreign('terminal_group_id')
                  ->references('id')
                  ->on('terminal_groups')
                  ->onDelete('cascade');

            $table->foreign('user_group_id')
                  ->references('id')
                  ->on('user_groups')
                  ->onDelete('cascade');

            // Composite primary key
            $table->primary(['terminal_group_id', 'user_group_id'], 'tg_ug_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('terminal_group_user_group');
    }
};

