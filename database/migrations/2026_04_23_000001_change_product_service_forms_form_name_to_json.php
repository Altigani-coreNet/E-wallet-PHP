<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_service_forms', function (Blueprint $table) {
            $table->json('form_name_tmp')->nullable()->after('form_name');
        });

        DB::table('product_service_forms')
            ->select(['id', 'form_name'])
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $rawName = is_string($row->form_name) ? trim($row->form_name) : '';
                    DB::table('product_service_forms')
                        ->where('id', $row->id)
                        ->update([
                            'form_name_tmp' => json_encode([
                                'en' => $rawName,
                                'ar' => '',
                            ], JSON_UNESCAPED_UNICODE),
                        ]);
                }
            });

        Schema::table('product_service_forms', function (Blueprint $table) {
            $table->dropColumn('form_name');
        });

        Schema::table('product_service_forms', function (Blueprint $table) {
            $table->renameColumn('form_name_tmp', 'form_name');
        });
    }

    public function down(): void
    {
        Schema::table('product_service_forms', function (Blueprint $table) {
            $table->string('form_name_tmp')->nullable()->after('form_name');
        });

        DB::table('product_service_forms')
            ->select(['id', 'form_name'])
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $decoded = [];
                    if (is_string($row->form_name)) {
                        $decoded = json_decode($row->form_name, true) ?: [];
                    } elseif (is_array($row->form_name)) {
                        $decoded = $row->form_name;
                    }
                    $fallback = $decoded['en'] ?? $decoded['ar'] ?? '';
                    DB::table('product_service_forms')
                        ->where('id', $row->id)
                        ->update(['form_name_tmp' => is_string($fallback) ? $fallback : '']);
                }
            });

        Schema::table('product_service_forms', function (Blueprint $table) {
            $table->dropColumn('form_name');
        });

        Schema::table('product_service_forms', function (Blueprint $table) {
            $table->renameColumn('form_name_tmp', 'form_name');
        });
    }
};
