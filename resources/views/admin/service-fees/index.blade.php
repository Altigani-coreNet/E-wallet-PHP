@extends('layouts.admin.admin_layout')

@section('title', 'Service Fees Management')

@section('breadcrumb')
<li class="breadcrumb-item text-gray-600">
    <a href="{{ route('admin.service-fees.index') }}" class="text-gray-600">Service Fees Management</a>
</li>
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
    <button type="button" class="btn btn-sm fw-bold btn-success" data-bs-toggle="modal" data-bs-target="#importServiceFeesModal">
        <i class="ki-duotone ki-file-up fs-3">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
        Import Service Fees
    </button>
    <!--end::Import button-->
    
    <!--begin::Primary button-->
    <a href="{{ route('admin.service-fees.create') }}" class="btn btn-sm fw-bold btn-primary">
        <i class="ki-duotone ki-plus fs-3">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
        Add New Service Fee
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
        <div class="card bg-white card-xl-stretch mb-5 mb-xl-8">
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
            <div class="card-body" id="filters-body">
                <div class="row g-4">
                    <!-- Search -->
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Search</label>
                        <input type="text" class="form-control" id="search-input" 
                               placeholder="Search by name or type">
                    </div>
                    
                    <!-- Type Filter -->
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Type</label>
                        <input type="text" class="form-control" id="type-filter" 
                               placeholder="Filter by type">
                    </div>
                    
                    <!-- Start Date -->
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Start Date</label>
                        <input type="date" class="form-control" id="date-from">
                    </div>
                    
                    <!-- End Date -->
                    <div class="col-md-3">
                        <label class="form-label fw-bold">End Date</label>
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
                    <h3>Service Fees Management</h3>
                </div>
                <!--begin::Card title-->
                <!--begin::Card toolbar-->
                <div class="card-toolbar">
                    <!--begin::Group actions-->
                    <div class="d-flex justify-content-end align-items-center d-none"
                         data-kt-customer-table-toolbar="selected">
                        <div class="fw-bolder me-5">
                            <span class="me-2" data-kt-customer-table-select="selected_count"></span>Selected
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
                @if(session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif
                
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5" id="service-fees-table">
                        <!--begin::Table head-->
                        <thead>
                        <!--begin::Table row-->
                        <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                            <th class="w-10px pe-2">
                                <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                    <input class="form-check-input" type="checkbox" data-kt-check="true"
                                           data-kt-check-target="#service-fees-table .form-check-input" value="1"/>
                                </div>
                            </th>
                            <th class="text-dark">ID</th>
                            <th class="min-w-200px text-dark">Name</th>
                            <th class="text-dark">Type</th>
                            <th class="text-dark">Fees</th>
                            <th class="text-dark">Created At</th>
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

<!--begin::Import Service Fees Modal-->
<div class="modal fade" id="importServiceFeesModal" tabindex="-1" aria-labelledby="importServiceFeesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importServiceFeesModalLabel">
                    <i class="ki-duotone ki-file-up fs-2 me-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    Import Service Fees
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="importServiceFeesForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="import_file" class="form-label">Select File</label>
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
                                <span>Please ensure your file contains columns: Name, Type, Fees. The Type field should contain the service fee type as text.</span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <a href="{{ route('admin.service-fees.export-template') }}" class="btn btn-sm btn-outline-primary">
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
                        <i class="ki-duotone ki-file-up fs-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!--end::Import Service Fees Modal-->
@endsection

@push('scripts')
<script>
    let search = '', type = '', dateFrom = '', dateTo = '';
    let serviceFeesTable = $('#service-fees-table').DataTable({
        dom: "tiplr"
        , serverSide: true
        , processing: true
        , autoWidth: false
        , scrollX: true
        , "language": {
            "url": "{{ asset('admin_assets/datatable-lang/' . app()->getLocale() . '.json') }}"
        }
        , ajax: {
            url: '{{ route("admin.service-fees.data")}}',
            data: (q) => {
                q.search = search;
                q.type = type;
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
                data: 'type'
                , name: 'type'
            },
            {
                data: 'fees'
                , name: 'fees'
            },
            {
                data: 'created_at'
                , name: 'created_at'
            },
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
        $('#service-fees-table').css('width', '100%');
        $('.table-responsive').css('width', '100%');
    });

    // Initialize filters on page load
    $(document).ready(function() {
        const isCollapsed = localStorage.getItem('serviceFeesFiltersCollapsed');
        const filtersBody = $('#filters-body');
        const filterIcon = $('#filter-icon');
        
        if (isCollapsed === null || isCollapsed === 'true') {
            filtersBody.hide();
            filterIcon.css('transform', 'rotate(90deg)');
            localStorage.setItem('serviceFeesFiltersCollapsed', 'true');
        }
    });

    // Filter event handlers
    $('#search-input').on('keyup', function () {
        search = $(this).val();
        serviceFeesTable.ajax.reload();
    });

    $('#type-filter').on('change', function () {
        type = $(this).val();
        serviceFeesTable.ajax.reload();
    });

    $('#date-from').on('change', function () {
        dateFrom = $(this).val();
        serviceFeesTable.ajax.reload();
    });

    $('#date-to').on('change', function () {
        dateTo = $(this).val();
        serviceFeesTable.ajax.reload();
    });

    // Clear filters button
    $('#clear-filters').on('click', function () {
        search = '';
        type = '';
        dateFrom = '';
        dateTo = '';
        
        $('#search-input').val('');
        $('#type-filter').val('');
        $('#date-from').val('');
        $('#date-to').val('');
        
        serviceFeesTable.ajax.reload();
    });

    // Toggle filters section
    $('#filters_button').on('click', function () {
        const filtersBody = $('#filters-body');
        const filterIcon = $('#filter-icon');
        
        if (filtersBody.is(':visible')) {
            filtersBody.slideUp(300);
            filterIcon.css('transform', 'rotate(90deg)');
            localStorage.setItem('serviceFeesFiltersCollapsed', 'true');
        } else {
            filtersBody.slideDown(300);
            filterIcon.css('transform', 'rotate(0deg)');
            localStorage.setItem('serviceFeesFiltersCollapsed', 'false');
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
                $('#bulk-delete').html('<i class="ki-duotone ki-spinner fs-2 rotate"></i> Deleting...');
                
                $.ajax({
                    url: '{{ route("admin.service-fees.bulk-delete") }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        ids: selectedIds
                    },
                    success: function(response) {
                        if (response.success) {
                            serviceFeesTable.ajax.reload();
                            toastr.success('Service fees deleted successfully');
                            $('#bulk-delete').html('Delete Selected');
                        } else {
                            toastr.error('Something went wrong');
                            $('#bulk-delete').html('Delete Selected');
                        }
                    },
                    error: function() {
                        toastr.error('Something went wrong');
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
            $('[data-kt-customer-table-toolbar="selected"]').removeClass('d-none');
            $('[data-kt-customer-table-select="selected_count"]').text(selectedIds.length);
        } else {
            $('[data-kt-customer-table-toolbar="selected"]').addClass('d-none');
        }
    });

    // Handle select all checkbox
    $(document).on('change', '[data-kt-check-target="#service-fees-table .form-check-input"]', function() {
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

    // Import service fees functionality
    $('#importServiceFeesForm').on('submit', function(e) {
        e.preventDefault();
        
        let formData = new FormData(this);
        let submitBtn = $('#importSubmitBtn');
        let originalText = submitBtn.html();
        
        submitBtn.html('<i class="ki-duotone ki-spinner fs-2 rotate"></i> Importing...');
        
        $.ajax({
            url: '{{ route("admin.service-fees.import") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Service fees imported successfully');
                    $('#importServiceFeesModal').modal('hide');
                    $('#importServiceFeesForm')[0].reset();
                    serviceFeesTable.ajax.reload();
                } else {
                    toastr.error(response.message || 'Import failed');
                }
            },
            error: function(xhr) {
                let errorMessage = 'Something went wrong';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                toastr.error(errorMessage);
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Reset form when modal is closed
    $('#importServiceFeesModal').on('hidden.bs.modal', function() {
        $('#importServiceFeesForm')[0].reset();
    });
</script>
@endpush
