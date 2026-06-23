<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class LatestTender extends Component
{
    /**
     * Create a new component instance.
     */
    public int $tender_count;

    public function __construct()
    {
        $this->tender_count = 7;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.latest-tender');
    }
}
