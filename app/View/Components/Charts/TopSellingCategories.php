<?php

namespace App\View\Components\Charts;

use Illuminate\View\Component;

class TopSellingCategories extends Component
{
    public $chartId;
    public $subtitle;
    public $categories;
    public $data;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($chartId = 'kt_charts_widget_5', $subtitle = null, $categories = [], $data = [])
    {
        $this->chartId = $chartId;
        $this->subtitle = $subtitle;
        $this->categories = $categories;
        $this->data = $data;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.charts.top-selling-categories');
    }
}
