<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\Support\CustomerAuthTestingDatabase;

class PrepareCustomerAuthTestDatabase extends Command
{
    protected $signature = 'test:prepare-customer-auth-db
                            {--fresh : Drop all tables and re-run migrations}
                            {--inspect : Print customer-auth schema status after migrate}';

    protected $description = 'Create and migrate the dedicated SQLite database for customer auth tests';

    public function handle(): int
    {
        $connection = CustomerAuthTestingDatabase::CONNECTION;

        config([
            'database.default' => $connection,
            'cache.default' => 'array',
            'session.driver' => 'array',
            'queue.default' => 'sync',
        ]);

        $path = CustomerAuthTestingDatabase::ensureExists();

        $this->info('Customer auth testing database: '.$path);

        if ($this->option('fresh')) {
            Artisan::call('migrate:fresh', [
                '--database' => $connection,
                '--force' => true,
            ]);
        } else {
            Artisan::call('migrate', [
                '--database' => $connection,
                '--force' => true,
            ]);
        }

        $this->output->write(Artisan::output());

        if ($this->option('inspect')) {
            $this->newLine();
            $this->inspectCustomerAuthSchema($connection);
        }

        $this->info('Customer auth testing database is ready.');

        return self::SUCCESS;
    }

    private function inspectCustomerAuthSchema(string $connection): void
    {
        $this->info('Customer auth schema audit:');

        $tables = [
            'customers' => [
                'password',
                'balance',
                'birth_date',
                'gender',
                'profile_image',
                'profile_completed',
                'phone',
            ],
            'customers_otp' => [
                'identifier',
                'channel',
                'code',
                'token',
                'is_verified',
                'expires_at',
            ],
            'countries' => [
                'code',
            ],
            'cities' => [
                'country_id',
            ],
        ];

        $rows = [];

        foreach ($tables as $table => $columns) {
            if (! Schema::connection($connection)->hasTable($table)) {
                $rows[] = [$table, 'MISSING', '-'];

                continue;
            }

            $missingColumns = array_values(array_filter(
                $columns,
                fn (string $column) => ! Schema::connection($connection)->hasColumn($table, $column),
            ));

            $rows[] = [
                $table,
                empty($missingColumns) ? 'OK' : 'PARTIAL',
                empty($missingColumns) ? 'all required columns present' : 'missing: '.implode(', ', $missingColumns),
            ];
        }

        $this->table(['Table', 'Status', 'Details'], $rows);
    }
}
