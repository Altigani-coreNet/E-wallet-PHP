<?php

use App\Models\Customer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'uuid')) {
                $table->uuid('uuid')->nullable()->unique()->after('id');
            }
        });

        Customer::query()
            ->whereNull('uuid')
            ->orderBy('id')
            ->each(function (Customer $customer) {
                $customer->forceFill(['uuid' => (string) Str::uuid()])->saveQuietly();
            });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'uuid')) {
                $table->dropUnique(['uuid']);
                $table->dropColumn('uuid');
            }
        });
    }
};
