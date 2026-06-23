<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            if (! Schema::hasColumn('countries', 'dial_code')) {
                $table->string('dial_code')->nullable()->unique()->after('code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            if (Schema::hasColumn('countries', 'dial_code')) {
                $table->dropUnique(['dial_code']);
                $table->dropColumn('dial_code');
            }
        });
    }
};
