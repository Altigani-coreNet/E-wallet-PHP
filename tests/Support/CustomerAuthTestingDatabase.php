<?php

namespace Tests\Support;

class CustomerAuthTestingDatabase
{
    public const CONNECTION = 'customer_auth_testing';

    public const SQLITE_PATH = 'testing/customer_auth.sqlite';

    public static function absolutePath(): string
    {
        return dirname(__DIR__, 2)
            .DIRECTORY_SEPARATOR
            .'database'
            .DIRECTORY_SEPARATOR
            .self::SQLITE_PATH;
    }

    public static function ensureExists(): string
    {
        $path = self::absolutePath();
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if (file_exists($path) && self::shouldResetDatabase($path)) {
            unlink($path);
        }

        if (! file_exists($path)) {
            touch($path);
        }

        return $path;
    }

    private static function shouldResetDatabase(string $databasePath): bool
    {
        $migrationsPath = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations';

        if (! is_dir($migrationsPath)) {
            return false;
        }

        $latestMigrationTime = 0;
        foreach (glob($migrationsPath.DIRECTORY_SEPARATOR.'*.php') ?: [] as $migrationFile) {
            $latestMigrationTime = max($latestMigrationTime, (int) filemtime($migrationFile));
        }

        return $latestMigrationTime > (int) filemtime($databasePath);
    }
}
