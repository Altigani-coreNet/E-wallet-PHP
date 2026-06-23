<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'password')) {
                $table->string('password')->nullable()->after('phone');
            }
            if (! Schema::hasColumn('customers', 'balance')) {
                $table->decimal('balance', 20, 2)->default(0)->after('password');
            }
            if (! Schema::hasColumn('customers', 'birth_date')) {
                $table->dateTime('birth_date')->nullable()->after('balance');
            }
            if (! Schema::hasColumn('customers', 'gender')) {
                $table->string('gender')->nullable()->after('birth_date');
            }
            if (! Schema::hasColumn('customers', 'profile_completed')) {
                $table->boolean('profile_completed')->default(false)->after('merchant_id');
            }
        });

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            Schema::table('customers', function (Blueprint $table) {
                $table->string('name')->nullable()->change();
                $table->string('email')->nullable()->change();
            });
        }

        Schema::table('customers', function (Blueprint $table) {
            if (! $this->indexExists('customers', 'customers_phone_unique')) {
                $table->unique('phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if ($this->indexExists('customers', 'customers_phone_unique')) {
                $table->dropUnique(['phone']);
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            $columns = ['password', 'balance', 'birth_date', 'gender', 'profile_completed'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('customers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = $connection->select("PRAGMA index_list('{$table}')");

            return collect($indexes)->contains(fn ($index) => $index->name === $indexName);
        }

        $indexes = $connection->select(
            'SHOW INDEX FROM '.$connection->getTablePrefix().$table.' WHERE Key_name = ?',
            [$indexName]
        );

        return count($indexes) > 0;
    }
};
