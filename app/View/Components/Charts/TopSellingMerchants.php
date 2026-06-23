<?php

namespace App\View\Components\Charts;

use Illuminate\View\Component;
use App\Repositories\MerchantRepository;

class TopSellingMerchants extends Component
{
    public $chartId;
    public $subtitle;
    public $labels;
    public $counts;
    public $amounts;
    public $merchants;
    public $data;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(
        MerchantRepository $merchantRepository,
        $chartId = 'kt_charts_widget_merchants',
        $subtitle = null,
        $limit = 10,
        $days = 30
    ) {
        $data = $merchantRepository->getTopSellingMerchants($limit, $days);
        // dd($data);
        $this->chartId = $chartId;
        $this->subtitle = $subtitle ?? "Top $limit merchants in last $days days";
        $this->labels = $data['labels'];
        $this->data = $data['counts'];
        $this->amounts = $data['amounts'];
        $this->merchants = $data['merchants'];
        
        // $this->data = $data['data'];
        $this->labels = [
            'Phones', 'Laptops', 'Headsets', 'Games', 
            'Keyboards', 'Monitors', 'Speakers'
        ];
        
        // Store the values that will be used in the chart
        $values = [160.2, 120.1, 150.7, 69.4, 78.5, 77.6, 69.8];
        
        // Assign to the public property that will be available in the view
        $this->data = $values;
        // dd($this->data);
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.charts.top-selling-merchants', [
            'data' => $this->data,
            'labels' => $this->labels,
            'merchants' => $this->merchants
        ]);
    }
}
