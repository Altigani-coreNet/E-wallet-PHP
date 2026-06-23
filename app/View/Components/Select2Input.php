<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Select2Input extends Component
{
    public function __construct(
        public string  $filedName,
        public string  $name,
        public string  $class,
        public ?string $url,
        public ?string $selectedUrl,
        public         $value = null,
        public         $nameValue = null
    )
    {
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.select2-input');
    }
}
