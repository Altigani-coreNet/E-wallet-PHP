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
        Schema::table('services', function (Blueprint $table) {
            $table->foreignUuid('sub_category_id')
                ->nullable()
                ->after('category_id')
                ->constrained('service_sub_categories')
                ->nullOnDelete();

            $table->index('sub_category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropForeign(['sub_category_id']);
            $table->dropIndex(['sub_category_id']);
            $table->dropColumn('sub_category_id');
        });
    }
};
