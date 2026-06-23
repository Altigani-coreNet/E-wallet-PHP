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

        if (! file_exists($path)) {
            touch($path);
        }

        return $path;
    }
}
