<?php

namespace App\View\Components;

use Illuminate\View\Component;

class TextArea extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public $class, $name, $value, $filedName;

    public function __construct($class, $name, $filedName = null, $value = null)
    {
        $this->name = $name;
        $this->class = $class;
        $this->value = $value;
        $this->filedName = $filedName ?? $name;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.text-area');
    }
}
