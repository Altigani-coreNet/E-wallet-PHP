@extends("layouts.admin.admin_layout")
@section('main-head' , __('translation.batches'))
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
        <li class="breadcrumb-item text-muted">{{ __('translation.batches') }}</li>
        <!--end::Item-->
    </ul>
    <!--end::Breadcrumb-->
@endsection
@section('toolbar_actions')
<div class="d-flex align-items-center gap-2 gap-lg-3">
    <!--begin::Filter menu-->
    <div class="m-0">
        <!--begin::Menu toggle-->
        <button id="filters_button" type="button" class="btn btn-sm btn-flex btn-secondary fw-bold">
        <i class="ki-duotone ki-filter fs-6 text-muted me-1">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>{{ __('translation.filter') }}</button>
        <!--end::Menu toggle-->
        <!--begin::Menu 1-->
       
        <!--end::Menu 1-->
    </div>
    <!--end::Filter menu-->
    <!--begin::Refresh button-->
    <button id="refresh-table" class="btn btn-sm btn-flex btn-light fw-bold me-2">
        <i class="ki-duotone ki-arrows-circle fs-6 text-muted me-1">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>{{ __('translation.refresh') }}
    </button>
    <!--end::Refresh button-->
    <!--begin::Secondary button-->
    <!--end::Secondary button-->    
    <!--begin::Export button-->
    <button id="export-batches" class="btn btn-sm fw-bold btn-success" 
            data-bs-toggle="tooltip" 
            data-bs-placement="top" 
            title="Export batches with current filters applied">
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
<style>
    #filter-summary {
        transition: all 0.3s ease;
    }
    
    #filter-summary:hover {
        background-color: rgba(0,0,0,0.05);
        border-radius: 4px;
        padding: 4px 8px;
        margin: -4px -8px;
    }
    
    .dataTables_empty {
        text-align: center;
        padding: 40px 20px;
        color: #6c757d;
    }
    
    .dataTables_empty:before {
        content: "📊";
        font-size: 48px;
        display: block;
        margin-bottom: 16px;
        opacity: 0.5;
    }
    
    .is-loading {
        position: relative;
        pointer-events: none;
    }
    
    .is-loading:after {
        content: "";
        position: absolute;
        top: 50%;
        left: 50%;
        width: 16px;
        height: 16px;
        margin: -8px 0 0 -8px;
        border: 2px solid #f3f3f3;
        border-top: 2px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>
    <div class="post d-flex flex-column-fluid" id="kt_post">
        <!--begin::Container-->
        <div id="kt_content_container" class="container-xxl">
            <!-- Statistics Cards -->
            <div class="row g-5 g-xl-8 mt-4">
                <div class="col-md-12 row">
                    <div class="col-sm-3 text-center">
                        <!--begin::Statistics Widget 5-->
                        <a href="#" class="card bg-light-dark hoverable card-xl-stretch mb-xl-8">
                            <!--begin::Body-->
                            <div class="card-body">
                                <!--end::Svg Icon-->
                                <div class="text-black fw-bolder fs-2 mb-2 mt-5" id="total-batches">{{ $statistics['total'] ?? 0 }}</div>
                                <div class="fw-bold text-black">{{ __('translation.total_batches') }}</div>
                            </div>
                            <!--end::Body-->
                        </a>
                        <!--end::Statistics Widget 5-->
                    </div>
                    <div class="col-sm-3 text-center">
                        <!--begin::Statistics Widget 5-->
                        <a href="#" class="card bg-light-success hoverable card-xl-stretch mb-5 mb-xl-8">
                            <!--begin::Body-->
                            <div class="card-body">
                                <!--begin::Svg Icon | path: icons/duotune/graphs/gra007.svg-->
                                <!--end::Svg Icon-->
                                <div class="text-black fw-bolder fs-2 mb-2 mt-5" id="settled-batches">{{ $statistics['settled'] ?? 0 }}</div>
                                <div class="fw-bold text-black">{{ __('translation.settled') }}</div>
                            </div>
                            <!--end::Body-->
                        </a>
                        <!--end::Statistics Widget 5-->
                    </div>
                    <div class="col-sm-3 text-center">
                        <!--begin::Statistics Widget 5-->
                        <a href="#" class="card bg-light-warning hoverable card-xl-stretch mb-xl-8">
                            <!--begin::Body-->
                            <div class="card-body">
                                <!--end::Svg Icon-->
                                <div class="text-black fw-bolder fs-2 mb-2 mt-5" id="pending-batches">{{ $statistics['pending'] ?? 0 }}</div>
                                <div class="fw-bold text-black">{{ __('translation.pending') }}</div>
                            </div>
                            <!--end::Body-->
                        </a>
                        <!--end::Statistics Widget 5-->
                    </div>
                    <div class="col-sm-3 text-center">
                        <!--begin::Statistics Widget 5-->
                        <a href="#" class="card bg-light-danger hoverable card-xl-stretch mb-xl-8">
                            <!--begin::Body-->
                            <div class="card-body">
                                <!--end::Svg Icon-->
                                <div class="text-black fw-bolder fs-2 mb-2 mt-5" id="failed-batches">{{ $statistics['failed'] ?? 0 }}</div>
                                <div class="fw-bold text-black">{{ __('translation.failed') }}</div>
                            </div>
                            <!--end::Body-->
                        </a>
                        <!--end::Statistics Widget 5-->
                    </div>
                </div>
            </div>

            <!-- Hidden input for selected IDs -->
            <input type="hidden" id="record-ids" value="">
            
            <div class="card bg-white card-xl-stretch mb-5 mb-xl-8 d-none" id="filters_card">
                <!--begin::Body-->
                <div class="card-body">
                    <div class="row">
                        <x:text-input class="col-md-3" name='search'
                                      filedname="search" value="{{old('')}}"/>
                        <x:select-options class="col-md-3" name="status"
                                          filed-name='status'
                                          :options="['pending', 'settled', 'failed']"
                                          value="{{old('status')}}"/>
                        <x:select-options class="col-md-3" name="merchant_id"
                                          filed-name='merchant_id'
                                          :options="$merchants->pluck('name', 'id')->toArray()"
                                          value="{{old('merchant_id')}}"/>
                        @if(!Auth::guard('admin')->user()->custom_region)
                        <x:select2-input class="col-md-3" name="country" filed-name="country_id" 
                                        url="{{route('countries.select')}}" />
                        @endif
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label for="from_date" class="form-label fw-bold">{{ __('translation.from_date') }}</label>
                            <input type="date" class="form-control" name="from_date" id="from_date" value="{{ old('from_date') }}" />
                        </div>
                        <div class="col-md-6">
                            <label for="to_date" class="form-label fw-bold">{{ __('translation.to_date') }}</label>
                            <input type="date" class="form-control" name="to_date" id="to_date" value="{{ old('to_date') }}" />
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-8">
                            <div id="filter-summary" class="text-muted fs-7 d-none">
                                <i class="ki-duotone ki-filter fs-6 text-muted me-1">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <span id="active-filters-count">0</span> {{ __('translation.active_filters') }}
                                <span class="ms-2 badge badge-light-primary fs-8" id="filter-details"></span>
                            </div>
                        </div>
                        <div class="col-4 text-end">
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
                            <button type="button" class="btn btn-danger" id="bulk-delete">
                                {{ __('translation.delete_selected') }}
                            </button>
                        </div>
                        <!--end::Group actions-->
                    </div>
                    <!--end::Card toolbar-->
                </div>
                <!--end::Card header-->
                <!--begin::Card body-->
                <div class="card-body pt-0">
                    <div class="table-reponsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-5" id="batches-table">
                            <!--begin::Table head-->
                            <thead>
                            <!--begin::Table row-->
                            <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                                <th class="w-10px pe-2">
                                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                        <input class="form-check-input" type="checkbox" data-kt-check="true"
                                               data-kt-check-target="#batches-table .form-check-input" value="1"/>
                                    </div>
                                </th>
                                <th class="text-dark">{{ __('translation.batch_number') }}</th>
                                <th class="text-dark">{{ __('translation.merchant') }}</th>
                                <th class="text-dark">{{ __('translation.status') }}</th>
                                <th class="text-dark">{{ __('translation.total_amount') }}</th>
                                <th class="text-dark">{{ __('translation.transaction_count') }}</th>
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

            {{-- @dd('profile'); --}}

            <!--end::Card-->
        </div>
        <!--end::Container-->
    </div>
@endsection

@push('scripts')
    <script>
        // Statistics data from server
        const batchStatistics = @json($statistics);
        
        let search, status, merchant_id, country_id, from_date, to_date;
        let batchesTable = $('#batches-table').DataTable({
            dom: "tiplr"
            , serverSide: true
            , processing: true
            , autoWidth: false
            , scrollX: true
            , pageLength: 25
            , lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "{{ __('translation.all') }}"]]
            , "language": {
                "url": "{{ asset('admin_assets/datatable-lang/' . app()->getLocale() . '.json') }}"
                , "emptyTable": "{{ __('translation.no_batches_found') }}"
                , "zeroRecords": "{{ __('translation.no_batches_match_your_search') }}"
            }
            , ajax: {
                url: '{{ route("admin.batches.data")}}',
                data: (q) => {
                    q.search = search;
                    q.status = status;
                    q.merchant_id = merchant_id;
                    q.country_id = country_id;
                    q.from_date = from_date;
                    q.to_date = to_date;
                },
                error: function (xhr, error, thrown) {
                    console.error('DataTable error:', error);
                    toastr.error('{{ __("translation.something_went_wrong") }}');
                }
            }
            , columns: [{
                data: 'record_select'
                , name: 'record_select'
                , searchable: false
                , sortable: false
                , width: '1%'
            },
                {
                    data: 'batch_number'
                    , name: 'batch_number'
                },
                {
                    data: 'merchant_id'
                    , name: 'merchant_id'
                },
                {
                    data: 'status'
                    , name: 'status'
                    , orderable: false
                    , searchable: false
                },
                {
                    data: 'total_amount'
                    , name: 'total_amount'
                },
                {
                    data: 'transaction_count'
                    , name: 'transaction_count'
                },
                {
                    data: 'created_at'
                    , name: 'created_at'
                },
                @if(!Auth::guard('admin')->user()->custom_region)
                {
                    data: 'country'
                    , name: 'country'
                },
                @endif
                {
                    data: 'actions'
                    , name: 'actions'
                    , searchable: false
                    , sortable: false
                    , width: '20%'
                }
            ]
            , order: [
                [6, 'desc']
            ]
            , drawCallback: function (settings) {
                $('.record__select').prop('checked', false);
                $('#record__select-all').prop('checked', false);
                $('#record-ids').val('');

                // Re-initialize KTMenu dropdowns here
                if (typeof KTMenu !== 'undefined' && typeof KTMenu.createInstances === 'function') {
                    KTMenu.createInstances();
                }
            }
            , initComplete: function () {
                // Hide the processing indicator when table is ready
                $(this).closest('.dataTables_wrapper').find('.dataTables_processing').hide();
            }
            , preDrawCallback: function(settings) {
                // Show processing indicator before each draw
                $(this).closest('.dataTables_wrapper').find('.dataTables_processing').show();
            }
        });

        // Ensure table maintains full width on window resize
        $(window).on('resize', function () {
            $('#batches-table').css('width', '100%');
            $('.table-reponsive').css('width', '100%');
        });

        // Initialize filter summary on page load
        updateFilterSummary();

        // Initialize tooltips
        // $('[data-bs-toggle="tooltip"]').tooltip();

        // Update statistics cards with data from server
        updateStatisticsCards();

        // Add loading state to filter inputs
        $('select, input').on('change keyup', function() {
            $(this).addClass('is-loading');
            setTimeout(() => {
                $(this).removeClass('is-loading');
            }, 1000);
        });

        $("[name='search']").attr("placeholder", "{{ __('translation.search_by_batch_number_merchant') }}");

        $("[name='search']").on('keyup', function () {
            search = $(this).val();
            // Add a small delay to avoid too many requests while typing
            clearTimeout(window.searchTimeout);
            window.searchTimeout = setTimeout(function() {
                updateFilterSummary();
                batchesTable.ajax.reload();
            }, 500);
        });

        $("[name='status']").on('change', function () {
            status = $(this).val();
            updateFilterSummary();
            batchesTable.ajax.reload();
        });

        $("[name='merchant_id']").on('change', function () {
            merchant_id = $(this).val();
            updateFilterSummary();
            batchesTable.ajax.reload();
        });

        @if(!Auth::guard('admin')->user()->custom_region)
        $("[name='country_id']").on('change', function () {
            country_id = $(this).val();
            updateFilterSummary();
            batchesTable.ajax.reload();
        });
        @endif

        $("[name='from_date']").on('change', function () {
            from_date = $(this).val();
            updateFilterSummary();
            batchesTable.ajax.reload();
        });

        $("[name='to_date']").on('change', function () {
            to_date = $(this).val();
            updateFilterSummary();
            batchesTable.ajax.reload();
        });

        // Handle bulk delete
        $(document).on('click', '#bulk-delete', function() {
            let selectedIds = $('#record-ids').val();
            
            if (!selectedIds) {
                toastr.warning('{{ __('translation.please_select_records') }}');
                return;
            }
            
            Swal.fire({
                title: '{{ __('translation.are_you_sure') }}',
                text: "{{ __('translation.you_wont_be_able_to_revert') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: '{{ __('translation.yes_delete_it') }}',
                cancelButtonText: '{{ __('translation.cancel') }}'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading state
                    $('#bulk-delete').html('<i class="ki-duotone ki-spinner fs-2 rotate"></i> {{ __("translation.deleting") }}...');
                    
                    // Note: Bulk delete for batches not implemented yet
                    toastr.info('Bulk delete for batches will be implemented soon');
                    $('#bulk-delete').html('{{ __("translation.delete_selected") }}');
                }
            });
        });

        // Handle checkbox selection
        $(document).on('change', '.record__select', function() {
            let selectedIds = [];
            $('.record__select:checked').each(function() {
                selectedIds.push($(this).val());
            });
            
            $('#record-ids').val(selectedIds.join(','));
            
            if (selectedIds.length > 0) {
                $('[data-kt-customer-table-toolbar="selected"]').removeClass('d-none');
                $('[data-kt-customer-table-select="selected_count"]').text(selectedIds.length);
            } else {
                $('[data-kt-customer-table-toolbar="selected"]').addClass('d-none');
            }
        });

        // Handle select all checkbox
        $(document).on('change', '[data-kt-check-target="#batches-table .form-check-input"]', function() {
            let isChecked = $(this).is(':checked');
            $('.record__select').prop('checked', isChecked);
            
            if (isChecked) {
                let allIds = [];
                $('.record__select').each(function() {
                    allIds.push($(this).val());
                });
                $('#record-ids').val(allIds.join(','));
                $('[data-kt-customer-table-toolbar="selected"]').removeClass('d-none');
                $('[data-kt-customer-table-select="selected_count"]').text(allIds.length);
            } else {
                $('#record-ids').val('');
                $('[data-kt-customer-table-toolbar="selected"]').addClass('d-none');
            }
        });

        // Filter card toggle functionality (delegated for reliability)
        $(document).on('click', '#filters_button', function(e) {
            e.preventDefault();
            console.log('filters_button clicked');
            const $card = $('#filters_card');
            if ($card.hasClass('d-none')) {
                $card.removeClass('d-none').hide().slideDown(250);
            } else {
                $card.slideUp(250, function(){
                    $card.addClass('d-none');
                });
            }
        });

        // Refresh table functionality
        $('#refresh-table').on('click', function() {
            $(this).addClass('spinner spinner-sm spinner-left');
            batchesTable.ajax.reload(function() {
                $('#refresh-table').removeClass('spinner spinner-sm spinner-left');
            });
        });

        // Function to update filter summary
        function updateFilterSummary() {
            let activeFilters = 0;
            let filterDetails = [];
            
            if (search) {
                activeFilters++;
                filterDetails.push(`Search: "${search}"`);
            }
            if (status) {
                activeFilters++;
                filterDetails.push(`Status: ${status}`);
            }
            if (merchant_id) {
                activeFilters++;
                let merchantName = $("[name='merchant_id'] option:selected").text();
                filterDetails.push(`Merchant: ${merchantName}`);
            }
            @if(!Auth::guard('admin')->user()->custom_region)
            if (country_id) {
                activeFilters++;
                let countryName = $("[name='country_id'] option:selected").text();
                filterDetails.push(`Country: ${countryName}`);
            }
            @endif
            if (from_date) {
                activeFilters++;
                filterDetails.push(`From: ${from_date}`);
            }
            if (to_date) {
                activeFilters++;
                filterDetails.push(`To: ${to_date}`);
            }
            
            if (activeFilters > 0) {
                $('#filter-summary').removeClass('d-none');
                $('#active-filters-count').text(activeFilters);
                // Add tooltip with filter details
                $('#filter-summary').attr('title', filterDetails.join(', '));
                // Update filter details badge
                $('#filter-details').text(filterDetails.slice(0, 2).join(', ') + (filterDetails.length > 2 ? '...' : ''));
            } else {
                $('#filter-summary').addClass('d-none');
            }
        }

        // Clear filters functionality
        $('#clear-filters').on('click', function() {
            // Clear all filter inputs
            $("[name='search']").val('');
            $("[name='status']").val('');
            $("[name='merchant_id']").val('');
            $("[name='from_date']").val('');
            $("[name='to_date']").val('');
            @if(!Auth::guard('admin')->user()->custom_region)
            $("[name='country_id']").val('').trigger('change');
            @endif
            
            // Reset filter variables
            search = '';
            status = '';
            merchant_id = '';
            country_id = '';
            from_date = '';
            to_date = '';
            
            // Update filter summary
            updateFilterSummary();
            
            // Reload the table
            batchesTable.ajax.reload();
        });

        // Export functionality with current filters
        $('#export-batches').on('click', function() {
            // Build export URL with current filters
            let exportUrl = '{{ route("admin.batches.export") }}?';
            let params = [];
            
            if (search) params.push(`search=${encodeURIComponent(search)}`);
            if (status) params.push(`status=${encodeURIComponent(status)}`);
            if (merchant_id) params.push(`merchant_id=${encodeURIComponent(merchant_id)}`);
            if (country_id) params.push(`country_id=${encodeURIComponent(country_id)}`);
            if (from_date) params.push(`from_date=${encodeURIComponent(from_date)}`);
            if (to_date) params.push(`to_date=${encodeURIComponent(to_date)}`);
            
            if (params.length > 0) {
                exportUrl += params.join('&');
            }
            
            // Open export in new window
            window.open(exportUrl, '_blank');
        });

        // Function to update statistics cards
        function updateStatisticsCards() {
            $('#total-batches').text(batchStatistics.total || 0);
            $('#settled-batches').text(batchStatistics.settled || 0);
            $('#pending-batches').text(batchStatistics.pending || 0);
            $('#failed-batches').text(batchStatistics.failed || 0);
        }
    </script>
@endpush
