<div class="card card-flush h-xl-100">
    <div class="card-header pt-5">
        <h3 class="card-title align-items-start flex-column">
            <span class="card-label fw-bold text-gray-900">{{ $title }}</span>
            @if($subtitle)
                <span class="text-gray-500 mt-1 fw-semibold fs-6">{{ $subtitle }}</span>
            @endif
        </h3>
        <div class="card-toolbar">
            <div class="d-flex gap-2">
                <span class="btn btn-sm btn-secondary">
                    From: {{ request('datetime_from') ? \Carbon\Carbon::parse(request('datetime_from'))->format('M d, Y H:i') : '...' }}
                </span>
                <span class="btn btn-sm btn-secondary">
                    To: {{ request('datetime_to') ? \Carbon\Carbon::parse(request('datetime_to'))->format('M d, Y H:i') : '...' }}
                </span>
            </div>
        </div>
    </div>
    <div class="card-body pb-0 pt-4">
        <div id="kt_charts_transaction_range_{{ $chartId }}" class="ms-n5 me-n3 min-h-auto w-100" style="height: 300px"></div>
    </div>
</div>

@push('scripts')
<script>
var KTRangeTransactionChart_{{ $chartId }} = function() {
    var chart = null;

    var getOptions = function(data) {
        var element = document.getElementById(`kt_charts_transaction_range_{{ $chartId }}`);
        if (!element) return null;

        var height = parseInt(KTUtil.css(element, "height"));
        var grayColor = KTUtil.getCssVariableValue("--bs-gray-500");
        var borderColor = KTUtil.getCssVariableValue("--bs-border-dashed-color");
        var colors = [
            KTUtil.getCssVariableValue("--bs-primary"),
            KTUtil.getCssVariableValue("--bs-danger"),
            KTUtil.getCssVariableValue("--bs-warning")
        ];

        return {
            series: data.series || [{ name: 'Amount', data: data.amounts || [] }],
            chart: {
                fontFamily: "inherit",
                type: "line",
                height: height,
                toolbar: { show: false }
            },
            dataLabels: { enabled: false },
            stroke: { curve: "smooth", width: 3 },
            xaxis: {
                categories: data.labels || [],
                axisBorder: { show: false },
                axisTicks: { show: false },
                labels: { style: { colors: grayColor, fontSize: "12px" } }
            },
            yaxis: {
                min: 0,
                labels: { style: { colors: grayColor, fontSize: "12px" } }
            },
            colors: colors,
            grid: {
                borderColor: borderColor,
                strokeDashArray: 4,
                yaxis: { lines: { show: true } }
            },
            markers: { strokeWidth: 3 }
        };
    };

    return {
        init: function() {
            var element = document.getElementById(`kt_charts_transaction_range_{{ $chartId }}`);
            if (!element) return;
            var data = @json($chartData);
            // Normalize to a single-series if provided in amounts/labels shape
            if (!data.series && data.amounts) {
                data.series = [{ name: 'Amount', data: data.amounts }];
            }
            var options = getOptions(data);
            chart = new ApexCharts(element, options);
            chart.render();

            KTThemeMode.on("kt.thememode.change", function() {
                if (chart) chart.destroy();
                var newOptions = getOptions(data);
                chart = new ApexCharts(element, newOptions);
                chart.render();
            });
        }
    };
}();

KTUtil.onDOMContentLoaded(function() {
    KTRangeTransactionChart_{{ $chartId }}.init();
});
</script>
@endpush

<style>
#kt_charts_transaction_range_{{ $chartId }} { min-height: 300px; }
</style>

