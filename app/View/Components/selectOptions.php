<?php

namespace App\View\Components;

use Illuminate\Support\Collection;
use Illuminate\View\Component;

class selectOptions extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */


    public function __construct(
        public string           $filedName,
        public array|Collection $options,
        public string           $name,
        public string           $class,
        public ?string          $value = null
    )
    {

    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.select-options');
    }
}
