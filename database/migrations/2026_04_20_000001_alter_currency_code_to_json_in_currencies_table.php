<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('currencies')->select('id', 'currency_code')->orderBy('id')->chunkById(100, function ($currencies) {
            foreach ($currencies as $currency) {
                $decoded = json_decode($currency->currency_code, true);
                $codeEn = is_array($decoded) ? ($decoded['en'] ?? '') : (string) $currency->currency_code;
                $codeAr = is_array($decoded) ? ($decoded['ar'] ?? $codeEn) : (string) $currency->currency_code;

                DB::table('currencies')
                    ->where('id', $currency->id)
                    ->update([
                        'currency_code' => json_encode([
                            'en' => $codeEn,
                            'ar' => $codeAr,
                        ], JSON_UNESCAPED_UNICODE),
                    ]);
            }
        }, 'id');

        Schema::table('currencies', function (Blueprint $table) {
            $table->json('currency_code')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('currencies')->select('id', 'currency_code')->orderBy('id')->chunkById(100, function ($currencies) {
            foreach ($currencies as $currency) {
                $decoded = json_decode($currency->currency_code, true);
                $fallback = is_array($decoded) ? ($decoded['en'] ?? ($decoded['ar'] ?? '')) : (string) $currency->currency_code;

                DB::table('currencies')
                    ->where('id', $currency->id)
                    ->update([
                        'currency_code' => $fallback,
                    ]);
            }
        }, 'id');

        Schema::table('currencies', function (Blueprint $table) {
            $table->string('currency_code')->change();
        });
    }
};
