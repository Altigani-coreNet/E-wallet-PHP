<?php

namespace App\View\Composers;

use App\Support\MailLocale;
use Illuminate\View\View;

class EmailLocaleViewComposer
{
    public function compose(View $view): void
    {
        $view->with(MailLocale::viewData());
    }
}
