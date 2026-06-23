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
        DB::table('currencies')->select('id', 'symbol')->orderBy('id')->chunkById(100, function ($currencies) {
            foreach ($currencies as $currency) {
                $decoded = json_decode($currency->symbol, true);
                $symbolEn = is_array($decoded) ? ($decoded['en'] ?? '') : (string) $currency->symbol;
                $symbolAr = is_array($decoded) ? ($decoded['ar'] ?? $symbolEn) : (string) $currency->symbol;

                DB::table('currencies')
                    ->where('id', $currency->id)
                    ->update([
                        'symbol' => json_encode([
                            'en' => $symbolEn,
                            'ar' => $symbolAr,
                        ], JSON_UNESCAPED_UNICODE),
                    ]);
            }
        }, 'id');

        Schema::table('currencies', function (Blueprint $table) {
            $table->json('symbol')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('currencies')->select('id', 'symbol')->orderBy('id')->chunkById(100, function ($currencies) {
            foreach ($currencies as $currency) {
                $decoded = json_decode($currency->symbol, true);
                $fallback = is_array($decoded) ? ($decoded['en'] ?? ($decoded['ar'] ?? '')) : (string) $currency->symbol;

                DB::table('currencies')
                    ->where('id', $currency->id)
                    ->update([
                        'symbol' => $fallback,
                    ]);
            }
        }, 'id');

        Schema::table('currencies', function (Blueprint $table) {
            $table->string('symbol')->change();
        });
    }
};
