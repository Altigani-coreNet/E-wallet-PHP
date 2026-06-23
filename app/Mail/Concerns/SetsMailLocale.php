<?php

namespace App\Mail\Concerns;

use App\Support\MailLocale;

trait SetsMailLocale
{
    protected function applyMailLocale(?string $locale = null): void
    {
        $this->locale = MailLocale::resolve($locale);
    }
}
