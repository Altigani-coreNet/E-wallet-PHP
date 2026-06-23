@extends("layouts.admin.admin_layout")
@section('main-head' , isset($type) && $type ? __('translation.' . $type . '_transactions') : __('translation.transactions_management'))
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
        <li class="breadcrumb-item text-muted">
            @if(isset($type) && $type)
                {{ __('translation.' . $type . '_transactions') }}
            @else
                {{ __('translation.transactions') }}
            @endif
        </li>
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
        <!--begin::Menu 1-->
       
        <!--end::Menu 1-->
    </div>
    <!--end::Filter menu-->
    <!--begin::Refresh button-->
    {{-- <button id="refresh-table" class="btn btn-sm btn-flex btn-light fw-bold me-2">
        <i class="ki-duotone ki-arrows-circle fs-6 text-muted me-1">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>{{ __('translation.refresh') }}
    </button> --}}
    <!--end::Refresh button-->
    <!--begin::Secondary button-->
    <!--end::Secondary button-->    
    <!--begin::Export button-->
    <button id="export-transactions" class="btn btn-sm fw-bold btn-success" 
            data-bs-toggle="tooltip" 
            data-bs-placement="top" 
            title="Export transactions with current filters applied">
        <i class="ki-duotone ki-file-down fs-3">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
        {{ __('translation.export_transactions') }}
    </button>
    <!--end::Export button-->
    <!--begin::Primary button-->
   
    <!--end::Primary button-->
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
            @if(isset($type) && $type)
                <div class="row g-5 g-xl-8 mt-4">
                    <div class="col-md-12">
                        <div class="alert alert-info d-flex align-items-center p-5">
                            <i class="ki-duotone ki-information fs-2hx text-info me-4">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                            </i>
                            <div class="d-flex flex-column">
                                <h4 class="mb-1">{{ __('translation.' . $type . '_transactions') }}</h4>
                                <span>{{ __('translation.showing_transactions_for_type', ['type' => __('translation.' . $type . '_transactions')]) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            
            @if(!isset(request()->type))
    <div class="row gy-5 g-xl-10">
        <!--begin::Sale Transactions-->
        <div class="col-xl-4 mb-xl-10">
            <div class="card card-flush h-xl-100 bg-light-success">
                <div class="card-header pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-800">Sale Transactions</span>
                        <span class="text-gray-500 mt-1 fw-semibold fs-6">Approved, Pending, Capture</span>
                    </h3>
                </div>
                {{-- @dd($saleTransactions) --}}
                <div class="card-body pt-2 row">
                    <div class="mb-2 col-6">
                        <span class="fs-2hx fw-bold d-block text-gray-800 me-2 mb-2 lh-1 ls-n2">{{ number_format($saleTransactions ?? 0) }}</span>
                    </div>
                    <div class="mb-2 col-6 d-flex justify-content-center align-items-center">
                        <span class="fs-2x  fw-semibold text-success">${{ number_format($saleTransactionsAmount ?? 0, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Sale Transactions-->
        
        <!--begin::Refund Transactions-->
        <div class="col-xl-4 mb-xl-10">
            <div class="card card-flush h-xl-100 bg-light-danger">
                <div class="card-header pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-800">Refund Transactions</span>
                        <span class="text-gray-500 mt-1 fw-semibold fs-6">Refunded transactions</span>
                    </h3>
                </div>
                <div class="card-body pt-2 row">
                    <div class="mb-2 col-6">
                        <span class="fs-2hx fw-bold d-block text-gray-800 me-2 mb-2 lh-1 ls-n2">{{ number_format($refundTransactions ?? 0) }}</span>
                    </div>
                    <div class="mb-2 col-6 d-flex justify-content-center align-items-center">
                        <span class="fs-2x fw-semibold text-danger">${{ number_format($refundTransactionsAmount ?? 0, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Refund Transactions-->
        
        <!--begin::Void Transactions-->
        <div class="col-xl-4 mb-xl-10">
            <div class="card card-flush h-xl-100 bg-light-dark">
                <div class="card-header pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-800">Void Transactions</span>
                        <span class="text-gray-500 mt-1 fw-semibold fs-6">Voided transactions</span>
                    </h3>
                </div>
                <div class="card-body pt-2 row">
                    <div class="mb-2 col-6">
                        <span class="fs-2hx fw-bold d-block text-gray-800 me-2 mb-2 lh-1 ls-n2">{{ number_format($voidTransactions ?? 0) }}</span>
                    </div>
                    <div class="mb-2 col-6 d-flex justify-content-center align-items-center">
                        <span class="fs-2x fw-semibold text-dark">${{ number_format($voidTransactionsAmount ?? 0, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Void Transactions-->
    </div>
            @endif

            <!-- Hidden input for selected IDs -->
            <input type="hidden" id="record-ids" value="">
            
            <div class="card bg-white card-xl-stretch mb-5 mb-xl-8 d-none" id="filters_card">
                <!--begin::Body-->
                <div class="card-body">
                                        <div class="row">
                        <x:text-input class="col-md-3" name='search'
                                      filedname="search" value="{{old('')}}"
                                      label="{{ __('translation.search') }}"/>
                        <x:select-options class="col-md-3" name="status"
                                          filed-name='status'
                                          :options="['APPROVED', 'DECLINED', 'PENDING']"
                                          value="{{old('status')}}"
                                          label="{{ __('translation.status') }}"/>
                       
                        <x:select2-input class="col-md-3" name="merchant" filed-name="merchant_id"
                                          url="{{route('merchants.select')}}"
                                          label="{{ __('translation.merchant') }}" />
                        <x:select2-input class="col-md-3" name="terminal" filed-name="terminal_id"
                                            url="{{route('terminals.select')}}"
                                            label="{{ __('translation.terminal') }}" />
                    </div>
                    <div class="row mt-3">
                        @if(!Auth::guard('admin')->user()->custom_region)
                        <x:select2-input class="col-md-4" name="country" filed-name="country_id" 
                                        url="{{route('countries.select')}}" />
                        @endif
                        <div class="col-md-4">
                            <label for="start_date" class="form-label">{{ __('translation.from_date') }}</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                   placeholder="{{ __('translation.from_date') }}">
                        </div>
                        <div class="col-md-4">
                            <label for="end_date" class="form-label">{{ __('translation.to_date') }}</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                   placeholder="{{ __('translation.to_date') }}">
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
                        <table class="table align-middle table-row-dashed fs-7 gy-5" id="transactions-table">
                            <!--begin::Table head-->
                            <thead>
                            <!--begin::Table row-->
                            <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                                <th class="w-10px pe-2">
                                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                        <input class="form-check-input" type="checkbox" data-kt-check="true"
                                               data-kt-check-target="#transactions-table .form-check-input" value="1"/>
                                    </div>
                                </th>
                                <th class="text-dark">{{ __('translation.transaction_id') }}</th>
                                <th class="text-dark">{{ __('translation.rrn') }}</th>
                                <th class="text-dark">{{ __('translation.merchant') }}</th>
                                <th class="text-dark">{{ __('translation.payment_method') }}</th>
                                <th class="text-dark">{{ __('translation.card_number') }}</th>
                                <th class="text-dark">{{ __('translation.amount') }}</th>
                                <th class="text-dark">{{ __('translation.batch_no') }}</th>
                                <th class="text-dark">{{ __('translation.sdk') }}</th>
                                <th class="text-dark">{{ __('translation.created_time') }}</th>
                                @if(!Auth::guard('admin')->user()->custom_region)
                                <th class="text-dark">{{ __('translation.country') }}</th>
                                @endif
                                <th class="text-dark">{{ __('translation.payment_type') }}</th>
                                <th class="text-dark">{{ __('translation.status') }}</th>
                                {{-- <th class="text-end text-dark">{{ __('translation.actions') }}</th> --}}
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
        // Wait for document to be ready
        $(document).ready(function() {
            let search, status, method, merchant_id, terminal_id, country_id, start_date, end_date;
            
            let transactionsTable = $('#transactions-table').DataTable({
                dom: "tiplr"
                , serverSide: true
                , processing: true
                , autoWidth: false
                , scrollX: true
                , pageLength: 25
                , lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "{{ __('translation.all') }}"]]
                , "language": {
                    "url": "{{ asset('admin_assets/datatable-lang/' . app()->getLocale() . '.json') }}"
                    , "emptyTable": "{{ __('translation.no_transactions_found') }}"
                    , "zeroRecords": "{{ __('translation.no_transactions_match_your_search') }}"
                }
                , ajax: {
                    url: '{{ route("admin.transactions.data")}}',
                    data: (q) => {
                        q.search = search;
                        q.status = status;
                        q.method = method;
                        q.merchant_id = merchant_id;
                        q.terminal_id = terminal_id;
                        q.country_id = country_id;
                        q.start_date = start_date;
                        q.end_date = end_date;
                        @if(isset($type) && $type)
                        q.type = '{{ $type }}';
                        @endif
                    },
                    error: function (xhr, error, thrown) {
                        console.error('DataTable error:', error);
                        if (typeof toastr !== 'undefined') {
                            toastr.error('{{ __("translation.something_went_wrong") }}');
                        }
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
                        data: 'transaction_id'
                        , name: 'transaction_id'
                    },
                    {
                        data: 'rrn'
                        , name: 'rrn'
                    },
                    {
                        data: 'merchant'
                        , name: 'merchant'
                    },
                    {
                        data: 'payment_method'
                        , name: 'payment_method'
                    },
                    {
                        data: 'card_number'
                        , name: 'card_number'
                    },
                    {
                        data: 'amount'
                        , name: 'amount'
                    },
                    {
                        data: 'batch_no'
                        , name: 'batch_no'
                    },
                    {
                        data: 'sdk'
                        , name: 'sdk'
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
                        data: 'payment_type'
                        , name: 'payment_type'
                    },
                    {
                        data: 'status'
                        , name: 'status'
                    }
                ]
                , order: [
                    [1, 'desc']
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
                $('#transactions-table').css('width', '100%');
                $('.table-reponsive').css('width', '100%');
            });

            // Initialize filter summary on page load
            updateFilterSummary();

            // Initialize tooltips
            $('[data-bs-toggle="tooltip"]').tooltip();

            // Add loading state to filter inputs
            $('select, input').on('change keyup', function() {
                $(this).addClass('is-loading');
                setTimeout(() => {
                    $(this).removeClass('is-loading');
                }, 1000);
            });

            $("[name='search']").attr("placeholder", "{{ __('translation.search_by_transaction_id_rrn_auth_code') }}");

            $("[name='search']").on('keyup', function () {
                search = $(this).val();
                // Add a small delay to avoid too many requests while typing
                clearTimeout(window.searchTimeout);
                window.searchTimeout = setTimeout(function() {
                    updateFilterSummary();
                    transactionsTable.ajax.reload();
                }, 500);
            });

            $("[name='status']").on('change', function () {
                status = $(this).val();
                updateFilterSummary();
                transactionsTable.ajax.reload();
            });

            $("[name='method']").on('change', function () {
                method = $(this).val();
                updateFilterSummary();
                transactionsTable.ajax.reload();
            });

            $("[name='merchant_id']").on('change', function () {
                merchant_id = $(this).val();
                updateFilterSummary();
                transactionsTable.ajax.reload();
            });

            $("[name='terminal_id']").on('change', function () {
                terminal_id = $(this).val();
                updateFilterSummary();
                transactionsTable.ajax.reload();
            });

            @if(!Auth::guard('admin')->user()->custom_region)
            $("[name='country_id']").on('change', function () {
                country_id = $(this).val();
                updateFilterSummary();
                transactionsTable.ajax.reload();
            });
            @endif

            $("[name='start_date']").on('change', function () {
                start_date = $(this).val();
                updateFilterSummary();
                transactionsTable.ajax.reload();
            });

            $("[name='end_date']").on('change', function () {
                end_date = $(this).val();
                updateFilterSummary();
                transactionsTable.ajax.reload();
            });

            // Handle bulk delete
            $(document).on('click', '#bulk-delete', function() {
                let selectedIds = $('#record-ids').val();
                
                if (!selectedIds) {
                    if (typeof toastr !== 'undefined') {
                        toastr.warning('{{ __('translation.please_select_records') }}');
                    }
                    return;
                }
                
                if (typeof Swal !== 'undefined') {
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
                            
                            $.ajax({
                                url: '{{ route("admin.transactions.bulk-delete") }}',
                                method: 'POST',
                                data: {
                                    _token: '{{ csrf_token() }}',
                                    ids: selectedIds
                                },
                                success: function(response) {
                                    if (response.success) {
                                        transactionsTable.ajax.reload();
                                        if (typeof toastr !== 'undefined') {
                                            toastr.success('{{ __('translation.transactions_deleted_successfully') }}');
                                        }
                                        // Reset bulk delete button
                                        $('#bulk-delete').html('{{ __("translation.delete_selected") }}');
                                    } else {
                                        if (typeof toastr !== 'undefined') {
                                            toastr.error('{{ __('translation.something_went_wrong') }}');
                                        }
                                        // Reset bulk delete button
                                        $('#bulk-delete').html('{{ __("translation.delete_selected") }}');
                                    }
                                },
                                error: function() {
                                    if (typeof toastr !== 'undefined') {
                                        toastr.error('{{ __('translation.something_went_wrong') }}');
                                    }
                                    // Reset bulk delete button
                                    $('#bulk-delete').html('{{ __("translation.delete_selected") }}');
                                }
                            });
                        }
                    });
                } else {
                    // Fallback to confirm if SweetAlert is not available
                    if (confirm('{{ __('translation.are_you_sure') }}')) {
                        // Show loading state
                        $('#bulk-delete').html('<i class="ki-duotone ki-spinner fs-2 rotate"></i> {{ __("translation.deleting") }}...');
                        
                        $.ajax({
                            url: '{{ route("admin.transactions.bulk-delete") }}',
                            method: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                ids: selectedIds
                            },
                            success: function(response) {
                                if (response.success) {
                                    transactionsTable.ajax.reload();
                                    if (typeof toastr !== 'undefined') {
                                        toastr.success('{{ __('translation.transactions_deleted_successfully') }}');
                                    }
                                    // Reset bulk delete button
                                    $('#bulk-delete').html('{{ __("translation.delete_selected") }}');
                                } else {
                                    if (typeof toastr !== 'undefined') {
                                        toastr.error('{{ __('translation.something_went_wrong') }}');
                                    }
                                    // Reset bulk delete button
                                    $('#bulk-delete').html('{{ __("translation.delete_selected") }}');
                                }
                            },
                            error: function() {
                                if (typeof toastr !== 'undefined') {
                                    toastr.error('{{ __('translation.something_went_wrong') }}');
                                }
                                // Reset bulk delete button
                                $('#bulk-delete').html('{{ __("translation.delete_selected") }}');
                            }
                        });
                    }
                }
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
            $(document).on('change', '[data-kt-check-target="#transactions-table .form-check-input"]', function() {
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

            // Filter card toggle functionality
            $('#filters_button').on('click', function(e) {
                e.preventDefault();
                $('#filters_card').toggleClass('d-none');
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
                if (method) {
                    activeFilters++;
                    filterDetails.push(`Method: ${method}`);
                }
                if (merchant_id) {
                    activeFilters++;
                    let merchantName = $("[name='merchant_id'] option:selected").text();
                    filterDetails.push(`Merchant: ${merchantName}`);
                }
                if (terminal_id) {
                    activeFilters++;
                    let terminalName = $("[name='terminal_id'] option:selected").text();
                    filterDetails.push(`Terminal: ${terminalName}`);
                }
                if (start_date) {
                    activeFilters++;
                    filterDetails.push(`From: ${start_date}`);
                }
                if (end_date) {
                    activeFilters++;
                    filterDetails.push(`To: ${end_date}`);
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
                $("[name='method']").val('');
                $("[name='merchant_id']").val('');
                $("[name='terminal_id']").val('');
                $("[name='start_date']").val('');
                $("[name='end_date']").val('');
                
                // Reset filter variables
                search = '';
                status = '';
                method = '';
                merchant_id = '';
                terminal_id = '';
                start_date = '';
                end_date = '';
                
                // Update filter summary
                updateFilterSummary();
                
                // Reload the table
                transactionsTable.ajax.reload();
            });

            // Export functionality with current filters
            $('#export-transactions').on('click', function() {
                // Build export URL with current filters
                let exportUrl = '{{ route("admin.transactions.export") }}?';
                
                let params = [];
                
                if (search) params.push('search=' + encodeURIComponent(search));
                if (status) params.push('status=' + encodeURIComponent(status));
                if (method) params.push('method=' + encodeURIComponent(method));
                if (merchant_id) params.push('merchant_id=' + encodeURIComponent(merchant_id));
                if (terminal_id) params.push('terminal_id=' + encodeURIComponent(terminal_id));
                if (start_date) params.push('start_date=' + encodeURIComponent(start_date));
                if (end_date) params.push('end_date=' + encodeURIComponent(end_date));
                @if(isset($type) && $type)
                params.push('type=' + encodeURIComponent('{{ $type }}'));
                @endif
                
                // Show export info
                let filterInfo = '';
                if (params.length > 0) {
                    filterInfo = ' with current filters';
                }
                
                // Check if there are any active filters
                let exportMessage = '';
                if (params.length === 0) {
                    exportMessage = 'Export all transactions? This may take a moment for large datasets.';
                } else {
                    exportMessage = 'Export filtered transactions' + filterInfo + '? This may take a moment.';
                }
                
                // Show confirmation dialog
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: '{{ __("translation.export_transactions") }}',
                        text: exportMessage,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#28a745',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Export',
                        cancelButtonText: '{{ __("translation.cancel") }}'
                    }).then((result) => {
                        
                        if (result.isConfirmed) {
                            
                            // Show loading state
                            $('#export-transactions').addClass('spinner spinner-sm spinner-left');
                            $('#export-transactions').prop('disabled', true);
                            
                            // Create temporary link and trigger download
                            let link = document.createElement('a');
                            link.href = exportUrl;
                            link.download = 'transactions_' + new Date().toISOString().slice(0, 10) + '.csv';
                            document.body.appendChild(link);
                            
                            // Add error handling for the download
                            link.onerror = function() {
                                if (typeof toastr !== 'undefined') {
                                    toastr.error('{{ __("translation.export_failed") }}');
                                }
                                $('#export-transactions').removeClass('spinner spinner-sm spinner-left');
                                $('#export-transactions').prop('disabled', false);
                            };
                            
                            link.click();
                            document.body.removeChild(link);
                            
                            // Show success message
                            if (typeof toastr !== 'undefined') {
                                toastr.success('{{ __("translation.export_started") }}' + filterInfo + '. {{ __("translation.check_downloads_folder") }}');
                            }
                            
                            // Reset button state
                            setTimeout(() => {
                                $('#export-transactions').removeClass('spinner spinner-sm spinner-left');
                                $('#export-transactions').prop('disabled', false);
                            }, 1000);
                        }
                    });
                } else {
                    // Fallback to confirm if SweetAlert is not available
                    if (confirm(exportMessage)) {
                        // Show loading state
                        $('#export-transactions').addClass('spinner spinner-sm spinner-left');
                        $('#export-transactions').prop('disabled', true);
                        
                        // Create temporary link and trigger download
                        let link = document.createElement('a');
                        link.href = exportUrl;
                        link.download = 'transactions_' + new Date().toISOString().slice(0, 10) + '.csv';
                        document.body.appendChild(link);
                        
                        // Add error handling for the download
                        link.onerror = function() {
                            if (typeof toastr !== 'undefined') {
                                toastr.error('{{ __("translation.export_failed") }}');
                            }
                            $('#export-transactions').removeClass('spinner spinner-sm spinner-left');
                            $('#export-transactions').prop('disabled', false);
                        };
                        
                        link.click();
                        document.body.removeChild(link);
                        
                        // Show success message
                        if (typeof toastr !== 'undefined') {
                            toastr.success('{{ __("translation.export_started") }}' + filterInfo + '. {{ __("translation.check_downloads_folder") }}');
                        }
                        
                        // Reset button state
                        setTimeout(() => {
                            $('#export-transactions').removeClass('spinner spinner-sm spinner-left');
                            $('#export-transactions').prop('disabled', false);
                        }, 1000);
                    }
                }
            });
        });
    </script>
@endpush 