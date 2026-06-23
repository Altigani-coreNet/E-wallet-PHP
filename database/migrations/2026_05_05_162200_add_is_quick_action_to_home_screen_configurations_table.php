<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('home_screen_configurations', function (Blueprint $table) {
            $table->boolean('is_quick_action')->default(false)->after('sort_order');
            $table->index('is_quick_action', 'home_cfg_quick_action_idx');
        });
    }

    public function down(): void
    {
        Schema::table('home_screen_configurations', function (Blueprint $table) {
            $table->dropIndex('home_cfg_quick_action_idx');
            $table->dropColumn('is_quick_action');
        });
    }
};
