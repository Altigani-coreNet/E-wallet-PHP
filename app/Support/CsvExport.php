<?php

namespace App\Support;

final class CsvExport
{
    /**
     * Force spreadsheet apps to treat a CSV cell as text (keeps leading zeros, +, etc.).
     */
    public static function asText(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return "\t".(string) $value;
    }
}
