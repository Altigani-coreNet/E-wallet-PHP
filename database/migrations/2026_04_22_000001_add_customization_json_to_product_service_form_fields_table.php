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
        Schema::table('product_service_form_fields', function (Blueprint $table) {
            if (! Schema::hasColumn('product_service_form_fields', 'customization_json')) {
                $table->json('customization_json')->nullable()->after('options_json');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_service_form_fields', function (Blueprint $table) {
            if (Schema::hasColumn('product_service_form_fields', 'customization_json')) {
                $table->dropColumn('customization_json');
            }
        });
    }
};
