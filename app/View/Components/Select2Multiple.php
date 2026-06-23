<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Select2Multiple extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string  $filedName,
        public string  $name,
        public string  $class,
        public ?string $url,
        public ?string $selectedUrl,
        public         $value = null,
    )
    {
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.select2-multiple');
    }
}
