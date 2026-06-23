<div class="card card-flush h-xl-100">
    <!--begin::Header-->
    <div class="card-header pt-5">
        <!--begin::Title-->
        <h3 class="card-title align-items-start flex-column">
            <span class="card-label fw-bold text-gray-900">{{ $title }}</span>
            @if($subtitle)
                <span class="text-gray-500 mt-1 fw-semibold fs-6">{{ $subtitle }}</span>
            @endif
        </h3>
        <!--end::Title-->
        <!--begin::Toolbar-->
        <div class="card-toolbar">
            @php($hasRange = request()->has('datetime_from') || request()->has('datetime_to'))
            @if(!$hasRange)
                <ul class="nav" id="kt_chart_transaction_tabs_{{ $chartId }}" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link btn btn-sm btn-color-muted btn-active btn-active-light fw-bold px-4 me-1 active" data-bs-toggle="tab" id="kt_charts_transaction_tab_1_{{ $chartId }}" href="#kt_chart_transaction_tab_content_1_{{ $chartId }}" aria-selected="true" role="tab">Daily</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link btn btn-sm btn-color-muted btn-active btn-active-light fw-bold px-4 me-1" data-bs-toggle="tab" id="kt_charts_transaction_tab_2_{{ $chartId }}" href="#kt_chart_transaction_tab_content_2_{{ $chartId }}" aria-selected="false" tabindex="-1" role="tab">Weekly</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link btn btn-sm btn-color-muted btn-active btn-active-light fw-bold px-4 me-1" data-bs-toggle="tab" id="kt_charts_transaction_tab_3_{{ $chartId }}" href="#kt_chart_transaction_tab_content_3_{{ $chartId }}" aria-selected="false" tabindex="-1" role="tab">Monthly</a>
                    </li>
                </ul>
            @else
                <span class="badge badge-light-primary">Custom range</span>
            @endif
        </div>
        <!--end::Toolbar-->
    </div>
    <!--end::Header-->
    <!--begin::Body-->
    <div class="card-body pb-0 pt-4">
        <!--begin::Tab content-->
        @php($hasRange = request()->has('datetime_from') || request()->has('datetime_to'))
        <div class="tab-content">
            @if($hasRange)
                <!-- Single range chart container (uses daily dataset over range) -->
                <div class="tab-pane fade active show">
                    <div id="kt_charts_transaction_daily_{{ $chartId }}" class="ms-n5 me-n3 min-h-auto w-100" style="height: 300px"></div>
                </div>
            @else
                <!--begin::Tab pane - Daily-->
                <div class="tab-pane fade active show" id="kt_chart_transaction_tab_content_1_{{ $chartId }}" role="tabpanel" aria-labelledby="kt_charts_transaction_tab_1_{{ $chartId }}">
                    <!--begin::Chart-->
                    <div id="kt_charts_transaction_daily_{{ $chartId }}" class="ms-n5 me-n3 min-h-auto w-100" style="height: 300px"></div>
                    <!--end::Chart-->
                </div>
                <!--end::Tab pane-->
                
                <!--begin::Tab pane - Weekly-->
                <div class="tab-pane fade" id="kt_chart_transaction_tab_content_2_{{ $chartId }}" role="tabpanel" aria-labelledby="kt_charts_transaction_tab_2_{{ $chartId }}">
                    <!--begin::Chart-->
                    <div id="kt_charts_transaction_weekly_{{ $chartId }}" class="ms-n5 me-n3 min-h-auto w-100" style="height: 300px"></div>
                    <!--end::Chart-->
                </div>
                <!--end::Tab pane-->
                
                <!--begin::Tab pane - Monthly-->
                <div class="tab-pane fade" id="kt_chart_transaction_tab_content_3_{{ $chartId }}" role="tabpanel" aria-labelledby="kt_charts_transaction_tab_3_{{ $chartId }}">
                    <!--begin::Chart-->
                    <div id="kt_charts_transaction_monthly_{{ $chartId }}" class="ms-n5 me-n3 min-h-auto w-100" style="height: 300px"></div>
                    <!--end::Chart-->
                </div>
                <!--end::Tab pane-->
            @endif
        </div>
        <!--end::Tab content-->
    </div>
    <!--end::Body-->
</div>

@push('scripts')
<script>
// Transaction Charts JavaScript using ApexCharts
var KTChartsTransactionCharts = function() {
    var charts = {
        daily: null,
        weekly: null,
        monthly: null
    };

    // Common chart options
    var getCommonOptions = function(data, period) {
        var element = document.getElementById(`kt_charts_transaction_${period}_{{ $chartId }}`);
        if (!element) return null;

        var height = parseInt(KTUtil.css(element, "height"));
        var grayColor = KTUtil.getCssVariableValue("--bs-gray-500");
        var borderColor = KTUtil.getCssVariableValue("--bs-border-dashed-color");
        var colors = [
            KTUtil.getCssVariableValue("--bs-primary"),   // Approved
            KTUtil.getCssVariableValue("--bs-danger"),    // Voided
            KTUtil.getCssVariableValue("--bs-warning")    // Refunded
        ];

        return {
            series: data.series,
            chart: {
                fontFamily: "inherit",
                type: "line",
                height: height,
                toolbar: { show: false }
            },
            plotOptions: {},
            legend: {
                show: true,
                position: "top",
                labels: {
                    colors: grayColor,
                    useSeriesColors: false
                }
            },
            dataLabels: { enabled: false },
            stroke: {
                curve: "smooth",
                show: true,
                width: 3,
                colors: colors
            },
            xaxis: {
                categories: data.labels,
                axisBorder: { show: false },
                axisTicks: { show: false },
                tickAmount: 6,
                labels: {
                    style: {
                        colors: grayColor,
                        fontSize: "12px"
                    }
                },
                crosshairs: {
                    position: "front",
                    stroke: {
                        color: colors,
                        width: 1,
                        dashArray: 3
                    }
                }
            },
            yaxis: {
                min: 0,
                tickAmount: 6,
                labels: {
                    style: { colors: grayColor, fontSize: "12px" }
                }
            },
            colors: colors,
            grid: {
                borderColor: borderColor,
                strokeDashArray: 4,
                yaxis: { lines: { show: true } }
            },
            markers: {
                strokeColors: colors,
                strokeWidth: 3
            }
        };
    };

    // Initialize charts
    var initChart = function(period) {
        var element = document.getElementById(`kt_charts_transaction_${period}_{{ $chartId }}`);
        if (!element) return;

        var data = @json($chartData)[period];
        var options = getCommonOptions(data, period);
        
        if (charts[period]) {
            charts[period].destroy();
        }
        
        charts[period] = new ApexCharts(element, options);
        charts[period].render();
    };

    var hasRange = Boolean(@json(request()->has('datetime_from') || request()->has('datetime_to')));

    return {
        init: function() {
            if (hasRange) {
                // Only render daily chart over the selected range
                initChart('daily');
            } else {
                // Initialize all charts
                initChart('daily');
                initChart('weekly');
                initChart('monthly');
            }

            // Handle theme changes
            KTThemeMode.on("kt.thememode.change", function() {
                if (charts.daily) charts.daily.destroy();
                if (!hasRange && charts.weekly) charts.weekly.destroy();
                if (!hasRange && charts.monthly) charts.monthly.destroy();
                
                if (hasRange) {
                    initChart('daily');
                } else {
                    initChart('daily');
                    initChart('weekly');
                    initChart('monthly');
                }
            });
        }
    };
}();

// Initialize Transaction Charts when DOM is ready
KTUtil.onDOMContentLoaded(function() {
    KTChartsTransactionCharts.init();
});
</script>
@endpush

<style>
#kt_chart_transaction_tabs_{{ $chartId }} {
    margin-bottom: 0;
}

#kt_charts_transaction_daily_{{ $chartId }},
#kt_charts_transaction_weekly_{{ $chartId }},
#kt_charts_transaction_monthly_{{ $chartId }} {
    min-height: 300px;
}
</style>