<?php

namespace App\View\Components;

use Illuminate\View\Component;

class EditRealStateCategory extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public $item, $types;
    public function __construct($item)
    {
        $this->item = $item;
        $this->types = \App\Models\RealStateCategory::TYPES;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.edit-real-state-category');
    }
}
