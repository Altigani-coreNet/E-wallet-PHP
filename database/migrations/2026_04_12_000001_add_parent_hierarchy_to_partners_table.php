<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Self-referential partner hierarchy: root partners vs sub-partners under a parent.
     * Migrates legacy `partner_id` (if present) into `parent_id`.
     */
    public function up(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            if (! Schema::hasColumn('partners', 'business_name')) {
                $table->string('business_name')->nullable()->after('name');
            }
            if (! Schema::hasColumn('partners', 'business_phone')) {
                $table->string('business_phone')->nullable()->after('phone');
            }
            if (! Schema::hasColumn('partners', 'parent_id')) {
                $table->uuid('parent_id')->nullable()->after('partner_category_id');
            }
            if (! Schema::hasColumn('partners', 'is_parent')) {
                $table->boolean('is_parent')->default(false)->after('parent_id');
            }
        });

        if (Schema::hasColumn('partners', 'partner_id')) {
            $rows = DB::table('partners')
                ->whereNotNull('partner_id')
                ->select('id', 'partner_id')
                ->get();

            foreach ($rows as $row) {
                DB::table('partners')
                    ->where('id', $row->id)
                    ->update(['parent_id' => $row->partner_id]);
            }

            Schema::table('partners', function (Blueprint $table) {
                $table->dropColumn('partner_id');
            });
        }

        Schema::table('partners', function (Blueprint $table) {
            if (Schema::hasColumn('partners', 'parent_id')) {
                $table->index('parent_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            if (Schema::hasColumn('partners', 'business_phone')) {
                $table->dropColumn('business_phone');
            }
            if (Schema::hasColumn('partners', 'business_name')) {
                $table->dropColumn('business_name');
            }
            if (Schema::hasColumn('partners', 'is_parent')) {
                $table->dropColumn('is_parent');
            }
        });

        Schema::table('partners', function (Blueprint $table) {
            if (Schema::hasColumn('partners', 'parent_id')) {
                $table->dropColumn('parent_id');
            }
        });

        Schema::table('partners', function (Blueprint $table) {
            if (! Schema::hasColumn('partners', 'partner_id')) {
                $table->uuid('partner_id')->nullable()->after('partner_category_id');
            }
        });
    }
};
