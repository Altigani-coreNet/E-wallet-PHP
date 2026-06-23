<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_screen_configurations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuidMorphs('configurable', 'home_cfg_configurable_idx');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            // $table->unique(['configurable_type', 'configurable_id'], 'home_screen_configurations_unique_item');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_screen_configurations');
    }
};
