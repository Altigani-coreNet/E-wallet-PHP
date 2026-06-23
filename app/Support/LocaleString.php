<?php

namespace App\Support;

/**
 * Pick a single display string from Spatie translatable / JSON locale maps for API + React.
 */
class LocaleString
{
    /**
     * @param  mixed  $value  string or array like ['en' => '…', 'ar' => '…']
     */
    public static function one(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (is_string($value)) {
            return $value !== '' ? $value : null;
        }
        if (is_array($value)) {
            $picked = $value['en'] ?? $value['ar'] ?? null;
            if (is_string($picked) && $picked !== '') {
                return $picked;
            }
            $first = reset($value);

            return is_string($first) && $first !== '' ? $first : null;
        }

        return null;
    }
}
