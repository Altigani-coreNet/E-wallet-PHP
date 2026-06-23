<?php

namespace App\View\Components;

use App\Models\Tender;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class TopTender extends Component
{
    /**
     * Create a new component instance.
     */
    public \Illuminate\Database\Eloquent\Collection $tenders;

    public function __construct()
    {
        $this->tenders = Tender::with([
            "User:Name,profile_image,id"
        ])->select('cost', "status", 'details')->limit(3)->orderBy('cost', 'desc')->get();

    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.top-tender');
    }
}
