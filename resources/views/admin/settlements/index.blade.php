@extends("layouts.admin.admin_layout")
@section('main-head' , __('translation.settlements_management'))
@section('breadcrumb')
@section('breadcrumbs')
    <!--begin::Breadcrumb-->
    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">
            <a href="index.html" class="text-muted text-hover-primary">{{ __('translation.home') }}</a>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">{{ __('translation.settlements') }}</li>
        <!--end::Item-->
    </ul>
    <!--end::Breadcrumb-->
@endsection

@section('toolbar_actions')
<div class="d-flex align-items-center gap-2 gap-lg-3">
    <!--begin::Filter menu-->
    <div class="m-0">
        <!--begin::Menu toggle-->
        <button id="filters_button" class="btn btn-sm btn-flex btn-secondary fw-bold" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
        <i class="ki-duotone ki-filter fs-6 text-muted me-1">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>{{ __('translation.filter') }}</button>
        <!--end::Menu toggle-->
    </div>
    <!--end::Filter menu-->
    <!--begin::Export button-->
    <button id="export-settlements" class="btn btn-sm fw-bold btn-success" 
            data-bs-toggle="tooltip" 
            data-bs-placement="top" 
            title="Export settlements with current filters applied">
        <i class="ki-duotone ki-file-down fs-3">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
        {{ __('translation.export') }}
    </button>
    <!--end::Export button-->
</div>
@endsection

@section('content')
    <div class="post d-flex flex-column-fluid" id="kt_post">
        <!--begin::Container-->
        <div id="kt_content_container" class="container-xxl">
            <div class="row g-5 g-xl-8 mt-4">
            </div>
            <!-- Hidden input for selected IDs -->
            <input type="hidden" id="record-ids" value="">
            
            <div class="card bg-white card-xl-stretch mb-5 mb-xl-8 d-none" id="filters_card">
                <!--begin::Body-->
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('translation.search') }}</label>
                            <input type="text" name="search" class="form-control" placeholder="{{ __('translation.search_settlements') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('translation.status') }}</label>
                            <select name="status" class="form-select">
                                <option value="">{{ __('translation.all_statuses') }}</option>
                                <option value="pending">{{ __('translation.pending') }}</option>
                                <option value="settled">{{ __('translation.settled') }}</option>
                                <option value="failed">{{ __('translation.failed') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mt-3">
                        @if(!Auth::guard('admin')->user()->custom_region)
                        <x:select2-input class="col-md-4" name="country" filed-name="country_id" 
                                        url="{{route('countries.select')}}" />
                        @endif
                        <div class="col-md-4">
                            <label class="form-label">{{ __('translation.from_date') }}</label>
                            <input type="date" name="from_date" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('translation.to_date') }}</label>
                            <input type="date" name="to_date" class="form-control">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12 text-end">
                            <button type="button" class="btn btn-secondary btn-sm" id="clear-filters">
                                <i class="ki-duotone ki-filter-remove fs-3">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                {{ __('translation.clear_filters') }}
                            </button>
                        </div>
                    </div>
                </div>
                <!--end::Body-->
            </div>

            <!--begin::Card-->
            <div class="card">
                <!--begin::Card header-->
                <div class="card-header border-0 pt-6">
                    <!--begin::Card title-->
                    <div class="card-title">
                    </div>
                    <!--begin::Card title-->
                    <!--begin::Card toolbar-->
                    <div class="card-toolbar">
                        <!--begin::Toolbar-->
                        <!--end::Toolbar-->
                        <!--begin::Group actions-->
                        <div class="d-flex justify-content-end align-items-center d-none"
                             data-kt-customer-table-toolbar="selected">
                            <div class="fw-bolder me-5">
                                <span class="me-2" data-kt-customer-table-select="selected_count"></span>{{ __('translation.selected') }}
                            </div>
                        </div>
                        <!--end::Group actions-->
                    </div>
                    <!--end::Card toolbar-->
                </div>
                <!--end::Card header-->
                <!--begin::Card body-->
                <div class="card-body pt-0">
                    <div class="table-reponsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5" id="settlements-table">
                            <!--begin::Table head-->
                            <thead>
                            <!--begin::Table row-->
                            <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                                <th class="w-10px pe-2">
                                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                        <input class="form-check-input" type="checkbox" data-kt-check="true"
                                               data-kt-check-target="#settlements-table .form-check-input" value="1"/>
                                    </div>
                                </th>
                                <th class="text-dark">{{ __('translation.id') }}</th>
                                <th class="min-w-125px text-dark">{{ __('translation.settlement_number') }}</th>
                                <th class="min-w-125px text-dark">{{ __('translation.batch') }}</th>
                                <th class="text-dark">{{ __('translation.merchant') }}</th>
                                <th class="text-dark">{{ __('translation.status') }}</th>
                                <th class="text-dark">{{ __('translation.amount') }}</th>
                                <th class="text-dark">{{ __('translation.transactions') }}</th>
                                <th class="text-dark">{{ __('translation.created_at') }}</th>
                                @if(!Auth::guard('admin')->user()->custom_region)
                                <th class="text-dark">{{ __('translation.country') }}</th>
                                @endif
                                <th class="text-end text-dark">{{ __('translation.actions') }}</th>
                            </tr>
                            <!--end::Table row-->
                            </thead>
                            <!--end::Table head-->
                            <!--begin::Table body-->
                            <!--end::Table body-->
                        </table>
                    </div>
                    <!--end::Table-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Card-->
        </div>
        <!--end::Container-->
    </div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Initialize DataTable
        var table = $('#settlements-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("admin.settlements.data") }}',
                data: function(d) {
                    d.search = $('input[name="search"]').val();
                    d.status = $('select[name="status"]').val();
                    d.country_id = $('select[name="country_id"]').val();
                    d.from_date = $('input[name="from_date"]').val();
                    d.to_date = $('input[name="to_date"]').val();
                }
            },
            columns: [
                {data: 'record_select', name: 'record_select', orderable: false, searchable: false},
                {data: 'id', name: 'id'},
                {data: 'settlement_number', name: 'settlement_number'},
                {data: 'batch_number', name: 'batch_number'},
                {data: 'merchant_name', name: 'merchant_name'},
                {data: 'status', name: 'status'},
                {data: 'total_amount', name: 'total_amount'},
                {data: 'transaction_count', name: 'transaction_count'},
                {data: 'created_at', name: 'created_at'},
                @if(!Auth::guard('admin')->user()->custom_region)
                {data: 'country', name: 'country'},
                @endif
                {data: 'actions', name: 'actions', orderable: false, searchable: false}
            ],
            order: [[1, 'desc']],
            pageLength: 15,
            lengthMenu: [15, 25, 50, 100],
            language: {
                url: '{{ asset("admin_assets/datatable-lang/en.json") }}'
            }
        });

        // Filter functionality
        $('#filters_button').click(function() {
            $('#filters_card').toggleClass('d-none');
        });

        // Apply filters
        $('input[name="search"]').on('keyup', function() {
            table.draw();
        });
        
        $('select[name="status"]').on('change', function() {
            table.draw();
        });

        $('select[name="country_id"]').on('change', function() {
            table.draw();
        });

        $('input[name="from_date"]').on('change', function() {
            table.draw();
        });

        $('input[name="to_date"]').on('change', function() {
            table.draw();
        });

        // Clear filters functionality
        $('#clear-filters').on('click', function() {
            $('input[name="search"]').val('');
            $('select[name="status"]').val('');
            $('select[name="country_id"]').val('').trigger('change');
            $('input[name="from_date"]').val('');
            $('input[name="to_date"]').val('');
            table.draw();
        });

        // Export functionality with current filters
        $('#export-settlements').on('click', function() {
            // Build export URL with current filters
            let exportUrl = '{{ route("admin.settlements.export") }}?';
            let params = [];
            
            let search = $('input[name="search"]').val();
            let status = $('select[name="status"]').val();
            let country_id = $('select[name="country_id"]').val();
            let from_date = $('input[name="from_date"]').val();
            let to_date = $('input[name="to_date"]').val();
            
            if (search) params.push(`search=${encodeURIComponent(search)}`);
            if (status) params.push(`status=${encodeURIComponent(status)}`);
            if (country_id) params.push(`country_id=${encodeURIComponent(country_id)}`);
            if (from_date) params.push(`from_date=${encodeURIComponent(from_date)}`);
            if (to_date) params.push(`to_date=${encodeURIComponent(to_date)}`);
            
            if (params.length > 0) {
                exportUrl += params.join('&');
            }
            
            // Open export in new window
            window.open(exportUrl, '_blank');
        });

        // Checkbox functionality
        $('input[data-kt-check="true"]').on('change', function() {
            var checked = $(this).is(':checked');
            $('#settlements-table .form-check-input').prop('checked', checked);
            updateSelectedCount();
        });

        $('#settlements-table').on('change', '.form-check-input', function() {
            updateSelectedCount();
        });

        function updateSelectedCount() {
            var checkedCount = $('#settlements-table .form-check-input:checked').length;
            if (checkedCount > 0) {
                $('[data-kt-customer-table-toolbar="selected"]').removeClass('d-none');
                $('[data-kt-customer-table-select="selected_count"]').text(checkedCount + ' items selected');
            } else {
                $('[data-kt-customer-table-toolbar="selected"]').addClass('d-none');
            }
        }
    });
</script>
@endpush
