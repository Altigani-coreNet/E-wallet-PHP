<?php

namespace App\View\Components;

use App\Models\RealState;
use Illuminate\View\Component;

class RentRealStateOwner extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public $realstates, $realstate;
    public function __construct($realstates ,RealState $realstate)
    {
        $this->realstates = $realstates;
        $this->realstate = $realstate;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.rent-real-state-owner');
    }
}
