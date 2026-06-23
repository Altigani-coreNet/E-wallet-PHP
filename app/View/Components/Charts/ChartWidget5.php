<?php

namespace App\View\Components\Charts;

use Illuminate\View\Component;

class ChartWidget5 extends Component
{
    public $title;
    public $subtitle;
    public $labels;
    public $data;  // This will hold our chart data

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(
        $title = null,
        $subtitle = null
    ) {
        $this->title = $title;
        $this->subtitle = $subtitle;
        
        // Sample data - replace this with your actual data source
        $this->labels = [
            'Phones', 'Laptops', 'Headsets', 'Games', 
            'Keyboards', 'Monitors', 'Speakers'
        ];
        
        // Store the values that will be used in the chart
        $values = [160.2, 120.1, 150.7, 69.4, 78.5, 77.6, 69.8];
        
        // Assign to the public property that will be available in the view
        $this->data = $values;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.charts.chart-widget-5', [
            'data' => $this->data,
            'labels' => $this->labels,
            'title' => $this->title,
            'subtitle' => $this->subtitle
        ]);
    }
}