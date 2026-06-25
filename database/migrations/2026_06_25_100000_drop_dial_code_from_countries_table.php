<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('countries', 'dial_code')) {
            return;
        }

        DB::table('countries')
            ->where(function ($query) {
                $query->whereNull('code')->orWhere('code', '');
            })
            ->whereNotNull('dial_code')
            ->update(['code' => DB::raw('dial_code')]);

        Schema::table('countries', function (Blueprint $table) {
            $table->dropUnique(['dial_code']);
            $table->dropColumn('dial_code');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('countries', 'dial_code')) {
            return;
        }

        Schema::table('countries', function (Blueprint $table) {
            $table->string('dial_code')->nullable()->unique()->after('code');
        });

        DB::table('countries')
            ->whereNotNull('code')
            ->update(['dial_code' => DB::raw('code')]);
    }
};
