@extends('layouts.admin.admin_layout')

@section('title', 'Branches Management')

@section('breadcrumbs')
    <!--begin::Breadcrumb-->
    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1" >
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
        <li class="breadcrumb-item text-muted">{{ __('translation.merchant_management') }}</li>
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <li class="breadcrumb-item text-muted">{{ __('translation.branches_list') }}</li>
        <!--end::Item-->
    </ul>
    <!--end::Breadcrumb-->
@endsection

@section('toolbar_actions')
<div class="d-flex align-items-center gap-2 gap-lg-3">
    <!--begin::Filter menu-->
    <div class="m-0">
        <!--begin::Menu toggle-->
        <button id="filters_button" class="btn btn-sm btn-flex btn-secondary fw-bold">
        <i class="ki-duotone ki-filter fs-6 text-muted me-1" id="filter-icon">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>Toggle Filters</button>
        <!--end::Menu toggle-->
    </div>
    <!--end::Filter menu-->
    
    <!--begin::Import button-->
    <button type="button" class="btn btn-sm fw-bold btn-success" data-bs-toggle="modal" data-bs-target="#importBranchesModal">
        <i class="ki-duotone ki-file-up fs-3">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
        Import Branches
    </button>
    <!--end::Import button-->
    
    <!--begin::Primary button-->
    <a href="{{ route('branches.create') }}" class="btn btn-sm fw-bold btn-primary">
        <i class="ki-duotone ki-plus fs-3">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
        Add New Branch
    </a>
    <!--end::Primary button-->
</div>
@endsection

@section('content')
    <style>
        #filters-body {
            transition: all 0.3s ease;
            display: none; /* Hidden by default */
        }
        
        #filter-icon {
            transition: transform 0.3s ease;
            transform: rotate(90deg); /* Rotated by default */
        }
        
        #filters_button:hover {
            transform: translateY(-1px);
        }
        
        #filters_button {
            transition: all 0.3s ease;
        }
    </style>
    
    <div class="post d-flex flex-column-fluid" id="kt_post">
        <!--begin::Container-->
        <div id="kt_content_container" class="container-xxl">
            <div class="row g-5 g-xl-8 mt-4">
            </div>
            <!-- Hidden input for selected IDs -->
            <input type="hidden" id="record-ids" value="">
            
            <!--begin::Filters Card-->
            <div class="card bg-white card-xl-stretch mb-5 mb-xl-8" id="filters-body">
                <!--begin::Card header-->
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h3 class="fw-bold m-0">Filters</h3>
                    </div>
                    <div class="card-toolbar">
                        <button type="button" class="btn btn-sm btn-light-primary" id="clear-filters">
                            <i class="ki-duotone ki-refresh fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            Clear Filters
                        </button>
                    </div>
                </div>
                <!--end::Card header-->
                <!--begin::Card body-->
                <div class="card-body" >
                    <div class="row g-4">
                        <!-- Search -->
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Search</label>
                            <input type="text" class="form-control" id="search-input" 
                                   placeholder="Search by name, address, merchant...">
                        </div>
                        
                        <!-- Status Filter -->
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Status</label>
                            <select class="form-select" id="status-filter">
                                <option value="">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="viewed">Viewed</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                                <option value="suspended">Suspended</option>
                                <option value="deleted">Deleted</option>
                            </select>
                        </div>
                        
                        <!-- Country Filter -->
                        @if(!Auth::guard('admin')->user()->custom_region)
                        <x:select2-input class="col-md-3" name="country" filed-name="country_id" 
                                        url="{{route('countries.select')}}" />
                        @endif
                        
                        <!-- Date Range -->
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Created Date From</label>
                            <input type="date" class="form-control" id="date-from">
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Created Date To</label>
                            <input type="date" class="form-control" id="date-to">
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-primary" id="apply-filters">
                                    <i class="ki-duotone ki-filter fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    Apply Filters
                                </button>
                                
                                {{-- <button type="button" class="btn btn-success" id="export-filtered">
                                    <i class="ki-duotone ki-download fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    Export Filtered
                                </button> --}}
                            </div>
                        </div>
                    </div>
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Filters Card-->

            <!-- Pending Branches Alert -->
            <div class="alert alert-warning d-flex align-items-center p-5 mb-5" id="pending-branches-alert" style="display: none !important;">
                <i class="ki-duotone ki-information-5 fs-2hx text-warning me-4">
                    <span class="path1"></span>
                    <span class="path2"></span>
                    <span class="path3"></span>
                    <span class="path4"></span>
                </i>
                <div class="d-flex flex-column">
                    <h5 class="mb-1">Pending Branch Requests</h5>
                    <span>You have <strong id="pending-count">0</strong> branch requests waiting for approval. Review and take action on these requests.</span>
                </div>
            </div>

            <!--begin::Card-->
            <div class="card">
                <!--begin::Card header-->
                <div class="card-header border-0 pt-6">
                    <!--begin::Card title-->
                    <div class="card-title">
                        <h3>Branches Management</h3>
                    </div>
                    <!--begin::Card title-->
                    <!--begin::Card toolbar-->
                    <div class="card-toolbar">
                        <!--begin::Toolbar-->
                        <div class="d-flex justify-content-end" data-kt-branches-table-toolbar="base">
                        </div>
                        <!--end::Toolbar-->
                        <!--begin::Group actions-->
                        <div class="d-flex justify-content-end align-items-center d-none"
                             data-kt-branches-table-toolbar="selected">
                            <div class="fw-bolder me-5">
                                <span class="me-2" data-kt-branches-table-select="selected_count"></span>Selected
                            </div>
                            <button type="button" class="btn btn-danger" id="bulk-delete">
                                Delete Selected
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
                        <table class="table align-middle table-row-dashed fs-6 gy-5" id="branches_table">
                            <!--begin::Table head-->
                            <thead>
                            <!--begin::Table row-->
                            <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                                <th class="w-10px pe-2">
                                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                        <input class="form-check-input" type="checkbox" data-kt-check="true"
                                               data-kt-check-target="#branches_table .form-check-input" value="1"/>
                                    </div>
                                </th>
                                <th class="text-dark">ID</th>
                                <th class="min-w-125px text-dark">Name</th>
                                <th class="min-w-125px text-dark">Address</th>
                                <th class="text-dark">Merchant</th>
                                <th class="text-dark">Status</th>
                                <th class="text-dark">Created At</th>
                                @if(!Auth::guard('admin')->user()->custom_region)
                                <th class="text-dark">Country</th>
                                @endif
                                <th class="text-end text-dark">Actions</th>
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

    <!--begin::Import Branches Modal-->
    <div class="modal fade" id="importBranchesModal" tabindex="-1" aria-labelledby="importBranchesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importBranchesModalLabel">
                        <i class="ki-duotone ki-file-up fs-2 me-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        Import Branches
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="importBranchesForm" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <x:select2-input class="col-md-12" name="merchant" filed-name="merchant_id"
                        url="{{route('merchants.select')}}" />
                        
                        <div class="mb-3">
                            <label for="import_file" class="form-label fw-bold">Select File <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="import_file" name="import_file" accept=".xlsx,.xls,.csv" required>
                            <div class="form-text">Supported formats: .xlsx, .xls, .csv</div>
                        </div>
                        
                        <div class="alert alert-info">
                            <div class="d-flex">
                                <i class="ki-duotone ki-information-5 fs-2hx text-info me-4">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                </i>
                                <div class="d-flex flex-column">
                                    <h5 class="mb-1">Import Instructions</h5>
                                    <span>Please ensure your file contains the following columns: Name, Address, Status. The first row should contain headers. All branches will be assigned to the selected merchant.</span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <a href="{{ route('branches.export-template') }}" class="btn btn-sm btn-outline-primary">
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
                        <button type="submit" class="btn btn-primary" id="importSubmitBtn">
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
    <!--end::Import Branches Modal-->

    <!--begin::Preview Import Modal-->
    <div class="modal fade" id="previewImportModal" tabindex="-1" aria-labelledby="previewImportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="previewImportModalLabel">
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
                            <span>Please review the data below. If everything looks correct, click "Confirm Import" to proceed.</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <strong>Selected Merchant:</strong> <span id="preview_merchant_name" class="text-primary"></span>
                    </div>

                    <div class="mb-3">
                        <strong>Total Rows:</strong> <span id="preview_total_rows" class="badge badge-primary"></span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover" id="preview_table">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Address</th>
                                    <th>Status</th>
                                    <th>Is Active</th>
                                </tr>
                            </thead>
                            <tbody id="preview_table_body">
                                <!-- Data will be populated here -->
                            </tbody>
                        </table>
                    </div>

                    <div id="preview_errors" class="alert alert-danger mt-3" style="display: none;">
                        <h5>Validation Errors:</h5>
                        <ul id="preview_errors_list"></ul>
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
                    <button type="button" class="btn btn-primary" id="confirmImportBtn">
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
    let search = '', status = '', country = '', dateFrom = '', dateTo = '';

    let branchesTable = $('#branches_table').DataTable({
        dom: "tiplr"
        , serverSide: true
        , processing: true
        , autoWidth: false
        , scrollX: true
        , "language": {
            "url": "{{ asset('admin_assets/datatable-lang/' . app()->getLocale() . '.json') }}"
        }
        , ajax: {
            url: '{{ route("branches.data")}}',
            data: (q) => {
                q.search = search;
                q.status = status;
                q.country_id = country;
                q.date_from = dateFrom;
                q.date_to = dateTo;
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
                data: 'id'
                , name: 'id'
            },
            {
                data: 'name'
                , name: 'name'
            },
            {
                data: 'address'
                , name: 'address'
            },
            {
                data: 'merchant_name'
                , name: 'merchant_name'
            },
            {
                data: 'status'
                , name: 'status'
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
    });

    // Ensure table maintains full width on window resize
    $(window).on('resize', function () {
        $('#branches_table').css('width', '100%');
        $('.table-reponsive').css('width', '100%');
    });

    // Filter event handlers
    $('#search-input').on('keyup', function () {
        search = $(this).val();
        branchesTable.ajax.reload();
    });

    $('#country_id').on('change', function () {
            country = $(this).val();
            branchesTable.ajax.reload();
        });

    $('#status-filter').on('change', function () {
        status = $(this).val();
        branchesTable.ajax.reload();
    });

    @if(!Auth::guard('admin')->user()->custom_region)
    $('#country-filter').on('change', function () {
        country = $(this).val();
        branchesTable.ajax.reload();
    });
    @endif

    $('#date-from').on('change', function () {
        dateFrom = $(this).val();
        branchesTable.ajax.reload();
    });

    $('#date-to').on('change', function () {
        dateTo = $(this).val();
        branchesTable.ajax.reload();
    });

    // Apply filters button
    $('#apply-filters').on('click', function () {
        branchesTable.ajax.reload();
    });

    // Clear filters button
    $('#clear-filters').on('click', function () {
        search = '';
        status = '';
        country = '';
        dateFrom = '';
        dateTo = '';
        
        $('#search-input').val('');
        $('#status-filter').val('');
        @if(!Auth::guard('admin')->user()->custom_region)
        $('#country-filter').val('').trigger('change');
        @endif
        $('#date-from').val('');
        $('#date-to').val('');
        
        branchesTable.ajax.reload();
    });

    // Export filtered results
    $('#export-filtered').on('click', function () {
        let params = new URLSearchParams({
            search: search,
            status: status,
            date_from: dateFrom,
            date_to: dateTo
        });
        
        window.open('{{ route("branches.export") }}?' + params.toString(), '_blank');
    });

    // Toggle filters section using the filter button
    $('#filters_button').on('click', function () {
        const filtersBody = $('#filters-body');
        const filterIcon = $('#filter-icon');
        
        if (filtersBody.is(':visible')) {
            // Collapse filters
            filtersBody.slideUp(300);
            filterIcon.css('transform', 'rotate(90deg)');
            localStorage.setItem('branchFiltersCollapsed', 'true');
        } else {
            // Expand filters
            filtersBody.slideDown(300);
            filterIcon.css('transform', 'rotate(0deg)');
            localStorage.setItem('branchFiltersCollapsed', 'false');
        }
    });

    // Check if filters should be collapsed on page load
    $(document).ready(function() {
        const isCollapsed = localStorage.getItem('branchFiltersCollapsed');
        const filtersBody = $('#filters-body');
        const filterIcon = $('#filter-icon');
        
        // Default to collapsed if no preference is set, or if user previously collapsed it
        if (isCollapsed === null || isCollapsed === 'true') {
            filtersBody.hide();
            filterIcon.css('transform', 'rotate(90deg)');
            localStorage.setItem('branchFiltersCollapsed', 'true');
        }
    });

    // Handle bulk delete
    $(document).on('click', '#bulk-delete', function() {
        let selectedIds = $('#record-ids').val();
        
        if (!selectedIds) {
            toastr.warning('Please select records to delete');
            return;
        }
        
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading state
                $('#bulk-delete').html('<i class="ki-duotone ki-spinner fs-2 rotate"></i> Deleting...');
                
                $.ajax({
                    url: '{{ route("branches.bulk-delete") }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        ids: selectedIds
                    },
                    success: function(response) {
                        if (response.success) {
                            branchesTable.ajax.reload();
                            toastr.success('Branches deleted successfully');
                            // Reset bulk delete button
                            $('#bulk-delete').html('Delete Selected');
                        } else {
                            toastr.error('Something went wrong');
                            // Reset bulk delete button
                            $('#bulk-delete').html('Delete Selected');
                        }
                    },
                    error: function() {
                        toastr.error('Something went wrong');
                        // Reset bulk delete button
                        $('#bulk-delete').html('Delete Selected');
                    }
                });
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
            $('[data-kt-branches-table-toolbar="selected"]').removeClass('d-none');
            $('[data-kt-branches-table-select="selected_count"]').text(selectedIds.length);
        } else {
            $('[data-kt-branches-table-toolbar="selected"]').addClass('d-none');
        }
    });

    // Handle select all checkbox
    $(document).on('change', '[data-kt-check-target="#branches_table .form-check-input"]', function() {
        let isChecked = $(this).is(':checked');
        $('.record__select').prop('checked', isChecked);
        
        if (isChecked) {
            let allIds = [];
            $('.record__select').each(function() {
                allIds.push($(this).val());
            });
            $('#record-ids').val(allIds.join(','));
            $('[data-kt-branches-table-toolbar="selected"]').removeClass('d-none');
            $('[data-kt-branches-table-select="selected_count"]').text(allIds.length);
        } else {
            $('#record-ids').val('');
            $('[data-kt-branches-table-toolbar="selected"]').addClass('d-none');
        }
    });

    // Store preview data globally
    let previewData = null;
    let previewMerchantId = null;
    let previewFile = null;

    // Import branches functionality - Preview first
    $('#importBranchesForm').on('submit', function(e) {
        e.preventDefault();
        
        // Validate merchant selection
        let merchantId = $('#merchant_id').val();
        if (!merchantId) {
            toastr.warning('Please select a merchant');
            return;
        }
        
        // Validate file selection
        let fileInput = $('#import_file')[0];
        if (!fileInput.files || !fileInput.files[0]) {
            toastr.warning('Please select a file to import');
            return;
        }
        
        let formData = new FormData(this);
        let submitBtn = $('#importSubmitBtn');
        let originalText = submitBtn.html();
        
        // Show loading state
        submitBtn.prop('disabled', true).html('<i class="ki-duotone ki-spinner fs-2 rotate"></i> Loading Preview...');
        
        // Call preview endpoint
        $.ajax({
            url: '{{ route("branches.import-preview") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // Store data for later import
                    previewData = response.data;
                    previewMerchantId = merchantId;
                    previewFile = fileInput.files[0];
                    
                    // Get merchant name
                    let merchantName = $('#merchant_id option:selected').text();
                    
                    // Populate preview modal
                    $('#preview_merchant_name').text(merchantName);
                    $('#preview_total_rows').text(response.data.length);
                    
                    // Build table rows
                    let tableBody = $('#preview_table_body');
                    tableBody.empty();
                    
                    response.data.forEach(function(row, index) {
                        let statusBadge = getStatusBadge(row.status || 'pending');
                        let activeBadge = row.is_active ? '<span class="badge badge-success">Yes</span>' : '<span class="badge badge-secondary">No</span>';
                        
                        tableBody.append(`
                            <tr>
                                <td>${index + 1}</td>
                                <td>${row.name || '<span class="text-danger">Missing</span>'}</td>
                                <td>${row.address || '<span class="text-danger">Missing</span>'}</td>
                                <td>${statusBadge}</td>
                                <td>${activeBadge}</td>
                            </tr>
                        `);
                    });
                    
                    // Show validation errors if any
                    if (response.errors && response.errors.length > 0) {
                        let errorsList = $('#preview_errors_list');
                        errorsList.empty();
                        response.errors.forEach(function(error) {
                            errorsList.append(`<li>${error}</li>`);
                        });
                        $('#preview_errors').show();
                    } else {
                        $('#preview_errors').hide();
                    }
                    
                    // Hide import modal and show preview modal
                    $('#importBranchesModal').modal('hide');
                    $('#previewImportModal').modal('show');
                } else {
                    toastr.error(response.message || 'Failed to preview data');
                }
            },
            error: function(xhr) {
                let errorMessage = 'Something went wrong';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    let errors = xhr.responseJSON.errors;
                    errorMessage = Object.values(errors).flat().join('<br>');
                }
                toastr.error(errorMessage);
            },
            complete: function() {
                // Reset button state
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Helper function to get status badge
    function getStatusBadge(status) {
        const badges = {
            'pending': '<span class="badge badge-warning">Pending</span>',
            'approved': '<span class="badge badge-success">Approved</span>',
            'rejected': '<span class="badge badge-danger">Rejected</span>',
            'suspended': '<span class="badge badge-secondary">Suspended</span>',
            'active': '<span class="badge badge-success">Active</span>'
        };
        return badges[status] || `<span class="badge badge-info">${status}</span>`;
    }

    // Confirm import functionality
    $('#confirmImportBtn').on('click', function() {
        if (!previewData || !previewMerchantId) {
            toastr.error('No preview data available');
            return;
        }
        
        let confirmBtn = $(this);
        let originalText = confirmBtn.html();
        
        // Show loading state
        confirmBtn.prop('disabled', true).html('<i class="ki-duotone ki-spinner fs-2 rotate"></i> Importing...');
        
        // Create form data with the original file
        let formData = new FormData();
        formData.append('import_file', previewFile);
        formData.append('merchant_id', previewMerchantId);
        formData.append('_token', '{{ csrf_token() }}');
        
        $.ajax({
            url: '{{ route("branches.import") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Branches imported successfully');
                    $('#previewImportModal').modal('hide');
                    $('#importBranchesForm')[0].reset();
                    $('#merchant_id').val('').trigger('change');
                    branchesTable.ajax.reload();
                    
                    // Clear preview data
                    previewData = null;
                    previewMerchantId = null;
                    previewFile = null;
                } else {
                    toastr.error(response.message || 'Import failed');
                }
            },
            error: function(xhr) {
                let errorMessage = 'Something went wrong';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    let errors = xhr.responseJSON.errors;
                    errorMessage = Object.values(errors).flat().join('<br>');
                }
                toastr.error(errorMessage);
            },
            complete: function() {
                // Reset button state
                confirmBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Reset form when modal is closed
    $('#importBranchesModal').on('hidden.bs.modal', function() {
        $('#importBranchesForm')[0].reset();
        $('#merchant_id').val('').trigger('change');
    });

    // Check for pending branches and show alert
    function checkPendingBranches() {
        $.ajax({
            url: '{{ route("branches.data") }}',
            method: 'GET',
            data: { status: 'pending' },
            success: function(response) {
                if (response.data && response.data.length > 0) {
                    $('#pending-count').text(response.data.length);
                    $('#pending-branches-alert').show();
                } else {
                    $('#pending-branches-alert').hide();
                }
            },
            error: function() {
                // Hide alert on error
                $('#pending-branches-alert').hide();
            }
        });
    }

    // Check for pending branches on page load
    checkPendingBranches();

    // Check for pending branches after table reload
    branchesTable.on('draw.dt', function() {
        checkPendingBranches();
    });
</script>
@endpush 