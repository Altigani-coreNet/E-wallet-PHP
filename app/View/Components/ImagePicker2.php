<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ImagePicker2 extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string  $name,
        public string  $class,
        public string  $filedName,
        public string  $realFiledId,
        public ?string $value = null,
        public bool    $disabled = false,
        public bool    $hidden = false)
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.image-picker2');
    }
}
