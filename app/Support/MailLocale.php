<?php

namespace App\Support;

class MailLocale
{
    public const SUPPORTED = ['en', 'ar'];

    public static function resolve(?string $preferred = null): string
    {
        if ($preferred !== null && in_array($preferred, self::SUPPORTED, true)) {
            return $preferred;
        }

        $locale = app()->getLocale();

        return in_array($locale, self::SUPPORTED, true) ? $locale : 'en';
    }

    /**
     * Blade variables for email templates (dir, alignment, RTL helpers).
     *
     * @return array<string, mixed>
     */
    public static function viewData(?string $preferred = null): array
    {
        $locale = static::resolve($preferred);
        $rtl = $locale === 'ar';

        return [
            'emailLocale' => $locale,
            'emailRtl' => $rtl,
            'emailDir' => $rtl ? 'rtl' : 'ltr',
            'emailAlign' => $rtl ? 'right' : 'left',
            'emailPadInline' => $rtl ? 'padding-right' : 'padding-left',
            'emailBorderInline' => $rtl ? 'border-right' : 'border-left',
        ];
    }
}
