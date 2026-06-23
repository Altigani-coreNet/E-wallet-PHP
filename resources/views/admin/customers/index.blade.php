@extends('layouts.admin.admin_layout')

@section('title', 'Customers Management')

@section('breadcrumbs')
    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
        <li class="breadcrumb-item text-muted">
            <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">{{ __('translation.home') }}</a>
        </li>
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <li class="breadcrumb-item text-muted">
            <a href="{{ route('admin.customers.index') }}" class="text-muted text-hover-primary">{{ __('translation.customers') }}</a>
        </li>
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <li class="breadcrumb-item text-muted">{{ __('translation.customers_management') }}</li>
    </ul>
@endsection

@section('toolbar_actions')
<div class="d-flex justify-content-end" data-kt-user-table-toolbar="base">
    <button id="filters_button" type="button" class="btn btn-secondary me-2 btn-sm">
        <i class="ki-duotone ki-filter fs-6 text-muted me-1" id="filter-icon">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>{{ __('translation.toggle_filters') }}
    </button>
    
    <!--begin::Import button-->
    <button type="button" class="btn btn-sm fw-bold btn-success me-2" data-bs-toggle="modal" data-bs-target="#importCustomersModal">
        <i class="ki-duotone ki-file-up fs-3">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
        Import Customers
    </button>
    <!--end::Import button-->
    
    <button type="button" class="btn btn-success me-2 btn-sm" id="export-filtered">
        <i class="ki-duotone ki-download fs-2">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
        {{ __('translation.export') }}
    </button>
    <!--begin::Add user-->
    <a href="{{ route('admin.customers.create') }}" class="btn btn-primary btn-sm">
        <i class="ki-duotone ki-plus fs-2">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>Add Customer
    </a>
    <!--end::Add user-->
</div>
@endsection
@section('content')
<div class="post d-flex flex-column-fluid" id="kt_post">
    <div id="kt_content_container" class="container-xxl">
        <!--begin::Filters Card-->
        <div class="card bg-white card-xl-stretch mb-5 mb-xl-8" id="filters-body" style="display: none;">
            <!--begin::Card header-->
            <div class="card-header border-0 pt-6">
                <div class="card-title">
                    <h3 class="fw-bold m-0">{{ __('translation.filters') }}</h3>
                </div>
                <div class="card-toolbar">
                    <button type="button" class="btn btn-sm btn-light-primary" id="clear-filters">
                        <i class="ki-duotone ki-refresh fs-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        {{ __('translation.clear_filters') }}
                    </button>
                </div>
            </div>
            <!--end::Card header-->
            <!--begin::Card body-->
            <div class="card-body">
                <div class="row g-4">
                    <!-- Search -->
                    <div class="col-md-4">
                        <label class="form-label fw-bold">{{ __('translation.search') }}</label>
                        <input type="text" class="form-control" id="search-input" placeholder="{{ __('translation.search') }}">
                    </div>

                    <!-- Merchant Filter -->
                    <div class="col-md-4">
                        <label class="form-label fw-bold">{{ __('translation.merchant') }}</label>
                        <select class="form-select" id="merchant-filter" name="merchant_id">
                            <option value="">{{ __('translation.all') }}</option>
                            @foreach(\App\Models\Merchant::select('id','name')->orderBy('name')->get() as $merchant)
                                <option value="{{ $merchant->id }}">{{ $merchant->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Country Filter -->
                    <x:select2-input class="col-md-4" name="country" filed-name="country_id" 
                                    url="{{route('countries.select')}}" />

                    <!-- Date Range -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold">{{ __('translation.created_date_from') }}</label>
                        <input type="date" class="form-control" id="date-from">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">{{ __('translation.created_date_to') }}</label>
                        <input type="date" class="form-control" id="date-to">
                    </div>
                </div>
            </div>
            <!--end::Card body-->
        </div>
        <!--end::Filters Card-->
        <!--begin::Card-->
        <div class="card">
            <!--begin::Card header-->
            <div class="card-header border-0 pt-6">
                <!--begin::Card title-->
                <div class="card-title">
                    <h3 class="fw-bold">{{ __('translation.customers_management') }}</h3>
                </div>
                <!--end::Card title-->
                
                <!--begin::Card toolbar-->
                <div class="card-toolbar">
                    <!--begin::Toolbar-->
                 
                    <!--end::Toolbar-->
                    <!--begin::Group actions-->
                    <div class="d-flex justify-content-end align-items-center d-none" data-kt-user-table-toolbar="selected">
                        <div class="fw-bold me-5">
                            <span class="me-2" data-kt-user-table-select="selected_count">0</span>Selected
                        </div>
                        <button type="button" class="btn btn-danger" data-kt-user-table-select="delete_selected">Delete Selected</button>
                    </div>
                    <!--end::Group actions-->
                </div>
                <!--end::Card toolbar-->
            </div>
            <!--end::Card header-->
            
            <!--begin::Card body-->
            <div class="card-body py-4">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                
                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                
                <!--begin::Table-->
                <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_table_customers">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th class="w-10px pe-2">
                                <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                    <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_table_customers .form-check-input" value="1" />
                                </div>
                            </th>
                            <th class="min-w-200px">Customer</th>
                            <th class="min-w-125px">Merchant</th>
                            <th class="min-w-125px">Address</th>
                            <th class="min-w-125px">Created</th>
                            @if(!Auth::guard('admin')->user()->custom_region)
                            <th class="min-w-125px">Country</th>
                            @endif
                            <th class="text-end min-w-100px">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 fw-semibold">
                    </tbody>
                </table>
                <!--end::Table-->
            </div>
            <!--end::Card body-->
        </div>
        <!--end::Card-->
    </div>
</div>

<!--begin::Import Customers Modal-->
<div class="modal fade" id="importCustomersModal" tabindex="-1" aria-labelledby="importCustomersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importCustomersModalLabel">
                    <i class="ki-duotone ki-file-up fs-2 me-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    Import Customers
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="importCustomersForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <x:select2-input class="col-md-12 mb-3" name="merchant" filed-name="import_customer_merchant_id"
                    url="{{route('merchants.select')}}" />
                    
                    <div class="mb-3">
                        <label for="import_customer_file" class="form-label fw-bold">{{ __('translation.select_file') }} <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="import_customer_file" name="import_file" accept=".xlsx,.xls,.csv" required>
                        <div class="form-text">{{ __('translation.supported_formats') }}: .xlsx, .xls, .csv</div>
                    </div>
                    
                    <div class="alert alert-info">
                        <div class="d-flex">
                            <i class="ki-duotone ki-information-5 fs-2hx text-info me-4">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                            </i>
                            <div class="d-flex flex-column">
                                <h5 class="mb-1">{{ __('translation.import_instructions') }}</h5>
                                <span>All customers will be assigned to the selected merchant. Duplicate emails will be skipped.</span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <a href="{{ route('admin.customers.export-template') }}" class="btn btn-sm btn-outline-primary">
                            <i class="ki-duotone ki-download fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            Download Template
                        </a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" id="importCustomersSubmitBtn">
                        <i class="ki-duotone ki-eye fs-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                        </i>
                        Preview Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!--end::Import Customers Modal-->

<!--begin::Preview Import Modal-->
<div class="modal fade" id="previewCustomersModal" tabindex="-1" aria-labelledby="previewCustomersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewCustomersModalLabel">
                    <i class="ki-duotone ki-eye fs-2 me-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                    </i>
                    Preview Import Data
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info d-flex align-items-center mb-5">
                    <i class="ki-duotone ki-information-5 fs-2hx text-info me-4">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                    </i>
                    <div class="d-flex flex-column">
                        <h5 class="mb-1">Review Before Import</h5>
                        <span>Please review the data below. Rows with errors or duplicates will be highlighted and skipped during import.</span>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Selected Merchant:</strong> <span id="preview_customer_merchant_name" class="text-primary"></span>
                    </div>
                    <div class="col-md-6">
                        <strong>Total Rows:</strong> <span id="preview_customer_total_rows" class="badge badge-primary"></span>
                        <strong class="ms-3">Valid:</strong> <span id="preview_customer_valid_rows" class="badge badge-success"></span>
                        <strong class="ms-3">Invalid:</strong> <span id="preview_customer_invalid_rows" class="badge badge-danger"></span>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover table-sm" id="preview_customers_table">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Address</th>
                                <th>Country</th>
                                <th>Validation</th>
                            </tr>
                        </thead>
                        <tbody id="preview_customers_table_body">
                            <!-- Data will be populated here -->
                        </tbody>
                    </table>
                </div>

                <div id="preview_customer_errors" class="alert alert-warning mt-3" style="display: none;">
                    <h5><i class="ki-duotone ki-information fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i> Issues Found:</h5>
                    <ul id="preview_customer_errors_list"></ul>
                    <p class="mb-0"><strong>Note:</strong> Rows with issues will be skipped during import. Only valid rows will be imported.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    Cancel
                </button>
                <button type="button" class="btn btn-primary" id="confirmCustomerImportBtn">
                    <i class="ki-duotone ki-check fs-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    Confirm Import
                </button>
            </div>
        </div>
    </div>
</div>
<!--end::Preview Import Modal-->
@endsection

@push('scripts')
<script>
    let search = '', merchantId = '', countryId = '', dateFrom = '', dateTo = '';
    let customersTable = $('#kt_table_customers').DataTable({
        dom: "tiplr",
        serverSide: true,
        processing: true,
        autoWidth: false,
        scrollX: true,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "{{ __('translation.all') }}"]],
        "language": {
            "url": "{{ asset('admin_assets/datatable-lang/' . app()->getLocale() . '.json') }}",
            "emptyTable": "{{ __('translation.no_customers_found') }}",
            "zeroRecords": "{{ __('translation.no_customers_match_your_search') }}"
        },
        ajax: {
            url: '{{ route("admin.customers.data") }}',
            data: function(q) {
                q.search = search;
                q.merchant_id = merchantId;
                q.country_id = countryId;
                q.date_from = dateFrom;
                q.date_to = dateTo;
            },
            error: function (xhr, error, thrown) {
                console.error('{{ __("translation.data_table_error") }}:', error);
                toastr.error('{{ __("translation.something_went_wrong") }}');
            }
        },
        columns: [
            {
                data: 'record_select',
                name: 'record_select',
                searchable: false,
                sortable: false,
                width: '5%'
            },
            {
                data: 'customer_info',
                name: 'name',
                searchable: true,
                sortable: false
            },
            {
                data: 'merchant_name',
                name: 'merchant_name'
            },
            {
                data: 'address_info',
                name: 'address_info'
            },
            {
                data: 'created_at',
                name: 'created_at'
            },
            @if(!Auth::guard('admin')->user()->custom_region)
            {
                data: 'country',
                name: 'country'
            },
            @endif
            {
                data: 'actions',
                name: 'actions',
                searchable: false,
                sortable: false,
                width: '20%'
            }
        ],
        order: [[1, 'asc']],
        drawCallback: function (settings) {
            // Re-initialize KTMenu dropdowns here
            if (typeof KTMenu !== 'undefined' && typeof KTMenu.createInstances === 'function') {
                KTMenu.createInstances();
            }
        }
    });

    // Toggle filters section
    $('#filters_button').on('click', function () {
        const filtersBody = $('#filters-body');
        const filterIcon = $('#filter-icon');
        if (filtersBody.is(':visible')) {
            filtersBody.slideUp(300);
            filterIcon.css('transform', 'rotate(90deg)');
            localStorage.setItem('customerFiltersCollapsed', 'true');
        } else {
            filtersBody.slideDown(300);
            filterIcon.css('transform', 'rotate(0deg)');
            localStorage.setItem('customerFiltersCollapsed', 'false');
        }
    });

    // On load restore filters collapsed state and init selects
    $(document).ready(function () {
        const isCollapsed = localStorage.getItem('customerFiltersCollapsed');
        const filtersBody = $('#filters-body');
        const filterIcon = $('#filter-icon');
        if (isCollapsed === null || isCollapsed === 'true') {
            filtersBody.hide();
            filterIcon.css('transform', 'rotate(90deg)');
            localStorage.setItem('customerFiltersCollapsed', 'true');
        }
        // Initialize select2 for merchant filter if available
        if ($.fn.select2) {
            $('#merchant-filter').select2({
                placeholder: '{{ __("translation.select_merchant") }}',
                allowClear: true,
                width: '100%'
            });
        }
    });

    // Filter handlers
    $('#search-input').on('keyup', function () {
        search = $(this).val();
        customersTable.ajax.reload();
    });

    $('#merchant-filter').on('change', function () {
        merchantId = $(this).val();
        customersTable.ajax.reload();
    });

    $('#country_id').on('change', function () {
        countryId = $(this).val();
            customersTable.ajax.reload();
        });

    $('#country-filter').on('change', function () {
        countryId = $(this).val();
        customersTable.ajax.reload();
    });

    $('#date-from').on('change', function () {
        dateFrom = $(this).val();
        customersTable.ajax.reload();
    });

    $('#date-to').on('change', function () {
        dateTo = $(this).val();
        customersTable.ajax.reload();
    });

    // Clear filters
    $('#clear-filters').on('click', function () {
        search = '';
        merchantId = '';
        dateFrom = '';
        dateTo = '';

        $('#search-input').val('');
        $('#merchant-filter').val('');
        if ($.fn.select2) {
            $('#merchant-filter').val('').trigger('change.select2');
            $('#country-filter').val('').trigger('change.select2');
        }
        $('#country-filter').val('');
        $('#date-from').val('');
        $('#date-to').val('');

        customersTable.ajax.reload();
    });

    // Export filtered results
    $('#export-filtered').on('click', function () {
        const params = new URLSearchParams({
            search: search || '',
            merchant_id: merchantId || '',
            country_id: countryId || '',
            date_from: dateFrom || '',
            date_to: dateTo || ''
        });
        window.open('{{ route('admin.customers.export') }}?' + params.toString(), '_blank');
    });

    // Delete Customer
    $(document).on('click', '.delete-customer', function() {
        var customerId = $(this).data('id');
        var customerName = $(this).data('name');
        
        if (confirm('{{ __("translation.are_you_sure_delete_customer") }}: ' + customerName + '{{ __("translation.question_mark") }}')) {
            $.ajax({
                url: '/admin/customers/' + customerId,
                method: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        customersTable.ajax.reload();
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr) {
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        toastr.error(xhr.responseJSON.message);
                    } else {
                        toastr.error('{{ __("translation.something_went_wrong") }}');
                    }
                }
            });
        }
    });

    // Bulk delete functionality
    $(document).on('click', '[data-kt-user-table-select="delete_selected"]', function() {
        var selectedIds = [];
        $('input[name="selected_customers[]"]:checked').each(function() {
            selectedIds.push($(this).val());
        });
        
        if (selectedIds.length === 0) {
            toastr.warning('Please select customers to delete.');
            return;
        }
        
        if (confirm('Are you sure you want to delete the selected customers?')) {
            $.ajax({
                url: '{{ route("admin.customers.bulk-delete") }}',
                type: 'POST',
                data: {
                    ids: selectedIds,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        customersTable.ajax.reload();
                        $('[data-kt-user-table-toolbar="selected"]').addClass('d-none');
                        $('[data-kt-user-table-toolbar="base"]').removeClass('d-none');
                    } else {
                        toastr.error(response.message || 'An error occurred');
                    }
                },
                error: function(xhr) {
                    var response = xhr.responseJSON;
                    if (response && response.error) {
                        toastr.error(response.error);
                    } else {
                        toastr.error('Delete failed. Please try again.');
                    }
                }
            });
        }
    });

    // Store preview data globally
    let previewCustomersData = null;
    let previewCustomersMerchantId = null;
    let previewCustomersFile = null;

    // Import customers functionality - Preview first
    $('#importCustomersForm').on('submit', function(e) {
        e.preventDefault();
        
        // Validate merchant selection
        let merchantId = $('#import_customer_merchant_id').val();
        if (!merchantId) {
            toastr.warning('Please select a merchant');
            return;
        }
        
        // Validate file selection
        let fileInput = $('#import_customer_file')[0];
        if (!fileInput.files || !fileInput.files[0]) {
            toastr.warning('Please select a file to import');
            return;
        }
        
        let formData = new FormData(this);
        formData.append('merchant_id', merchantId);
        let submitBtn = $('#importCustomersSubmitBtn');
        let originalText = submitBtn.html();
        
        submitBtn.prop('disabled', true).html('<i class="ki-duotone ki-spinner fs-2 rotate"></i> Loading Preview...');
        
        // Call preview endpoint
        $.ajax({
            url: '{{ route("admin.customers.import-preview") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // Store data for later import
                    previewCustomersData = response.data;
                    previewCustomersMerchantId = merchantId;
                    previewCustomersFile = fileInput.files[0];
                    
                    // Get merchant name
                    let merchantName = $('#import_customer_merchant_id option:selected').text();
                    
                    // Count valid and invalid rows
                    let validRows = response.data.filter(row => row.is_valid).length;
                    let invalidRows = response.data.length - validRows;
                    
                    // Populate preview modal
                    $('#preview_customer_merchant_name').text(merchantName);
                    $('#preview_customer_total_rows').text(response.data.length);
                    $('#preview_customer_valid_rows').text(validRows);
                    $('#preview_customer_invalid_rows').text(invalidRows);
                    
                    // Build table rows
                    let tableBody = $('#preview_customers_table_body');
                    tableBody.empty();
                    
                    response.data.forEach(function(row, index) {
                        let rowClass = row.is_valid ? '' : 'table-danger';
                        let validationBadge = row.is_valid 
                            ? '<span class="badge badge-success">Valid</span>' 
                            : '<span class="badge badge-danger">Invalid</span>';
                        
                        tableBody.append(`
                            <tr class="${rowClass}">
                                <td>${index + 1}</td>
                                <td>${row.name || '<span class="text-muted">-</span>'}</td>
                                <td>${row.email || '<span class="text-muted">-</span>'}</td>
                                <td>${row.phone || '<span class="text-muted">-</span>'}</td>
                                <td>${row.address || '<span class="text-muted">-</span>'}</td>
                                <td>${row.country_name || '<span class="text-muted">-</span>'}</td>
                                <td>${validationBadge}${row.errors ? '<br><small class="text-danger">' + row.errors + '</small>' : ''}</td>
                            </tr>
                        `);
                    });
                    
                    // Show validation errors if any
                    if (response.errors && response.errors.length > 0) {
                        let errorsList = $('#preview_customer_errors_list');
                        errorsList.empty();
                        response.errors.forEach(function(error) {
                            errorsList.append(`<li>${error}</li>`);
                        });
                        $('#preview_customer_errors').show();
                    } else {
                        $('#preview_customer_errors').hide();
                    }
                    
                    // Hide import modal and show preview modal
                    $('#importCustomersModal').modal('hide');
                    $('#previewCustomersModal').modal('show');
                } else {
                    toastr.error(response.message || 'Failed to preview data');
                }
            },
            error: function(xhr) {
                let errorMessage = '{{ __("translation.something_went_wrong") }}';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    let errors = xhr.responseJSON.errors;
                    errorMessage = Object.values(errors).flat().join('<br>');
                }
                toastr.error(errorMessage);
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Confirm import functionality
    $('#confirmCustomerImportBtn').on('click', function() {
        if (!previewCustomersData || !previewCustomersMerchantId) {
            toastr.error('No preview data available');
            return;
        }
        
        let confirmBtn = $(this);
        let originalText = confirmBtn.html();
        
        // Show loading state
        confirmBtn.prop('disabled', true).html('<i class="ki-duotone ki-spinner fs-2 rotate"></i> Importing...');
        
        // Create form data with the original file
        let formData = new FormData();
        formData.append('import_file', previewCustomersFile);
        formData.append('merchant_id', previewCustomersMerchantId);
        formData.append('_token', '{{ csrf_token() }}');
        
        $.ajax({
            url: '{{ route("admin.customers.import") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    let message = response.message || 'Customers imported successfully';
                    if (response.skipped_count && response.skipped_count > 0) {
                        message += ` (${response.imported_count} imported, ${response.skipped_count} skipped due to duplicates or errors)`;
                    }
                    toastr.success(message);
                    $('#previewCustomersModal').modal('hide');
                    $('#importCustomersForm')[0].reset();
                    $('#import_customer_merchant_id').val('').trigger('change');
                    customersTable.ajax.reload();
                    
                    // Clear preview data
                    previewCustomersData = null;
                    previewCustomersMerchantId = null;
                    previewCustomersFile = null;
                } else {
                    toastr.error(response.message || 'Import failed');
                }
            },
            error: function(xhr) {
                let errorMessage = '{{ __("translation.something_went_wrong") }}';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    let errors = xhr.responseJSON.errors;
                    errorMessage = Object.values(errors).flat().join('<br>');
                }
                toastr.error(errorMessage);
            },
            complete: function() {
                confirmBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Reset form when modal is closed
    $('#importCustomersModal').on('hidden.bs.modal', function() {
        $('#importCustomersForm')[0].reset();
        $('#import_customer_merchant_id').val('').trigger('change');
    });
</script>
@endpush


