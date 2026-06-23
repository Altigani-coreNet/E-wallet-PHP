@extends('layouts.merchant.merchant_layout')

@section('main-head', __('translation.customers_management'))

@section('breadcrumbs')
    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
        <li class="breadcrumb-item text-muted">
            <a href="{{ route('merchant.dashboard') }}" class="text-muted text-hover-primary">{{ __('translation.home') }}</a>
        </li>
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <li class="breadcrumb-item text-muted">{{ __('translation.customers') }}</li>
    </ul>
@endsection

@section('toolbar_actions')
<div class="d-flex align-items-center gap-2 gap-lg-3">
    <button id="filter-toggle" class="btn btn-sm btn-flex btn-light fw-bold me-2">
        <i class="ki-duotone ki-filter fs-6 text-muted me-1">
            <span class="path1"></span>
            <span class="path2"></span>
            <span class="path3"></span>
        </i>{{ __('translation.filter') }}
    </button>
    
    @if(auth()->user()->can('customers') || auth()->user()->can('create_customers'))
    <!--begin::Create button-->
    <a href="{{ route('merchant.customers.create') }}" class="btn btn-sm btn-flex btn-primary fw-bold">
        <i class="ki-duotone ki-plus fs-6 text-white me-1">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
        {{ __('translation.add_customer') }}
    </a>
    <!--end::Create button-->
    @endif
    
    <!--begin::Import Customers button-->
    <button type="button" class="btn btn-sm btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#importCustomersModal">
        <i class="ki-duotone ki-duotone ki-file-up fs-6 me-1">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
        Import
    </button>
    <!--end::Import Customers button-->
    
    <!--begin::Filter button-->
    
    <!--end::Filter button-->
    
    <!--begin::Export button-->
    <button id="export-customers" class="btn btn-sm btn-flex btn-success fw-bold me-2">
        <i class="ki-duotone ki-file-down fs-6 text-white me-1">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>{{ __('translation.export') }}
    </button>
    <!--end::Export button-->
    
    <!--begin::Refresh button-->
    {{-- <button id="refresh-table" class="btn btn-sm btn-flex btn-light fw-bold me-2">
        <i class="ki-duotone ki-arrows-circle fs-6 text-muted me-1">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>{{ __('translation.refresh') }}
    </button> --}}
    <!--end::Refresh button-->
</div>
@endsection

@section('content')
<div class="post d-flex flex-column-fluid" id="kt_post">
    <div id="kt_content_container" class="container-xxl">
        <!--begin::Card-->
        <div id="filter-card" class="card mb-5" style="display: none;">
            <div class="card-header">
                <div class="card-title">
                    <h5 class="card-title mb-0">{{ __('translation.filter_customers') }}</h5>
                </div>
                <div class="card-toolbar">
                    <button type="button" class="btn btn-sm btn-secondary" id="clearFilters">
                        <i class="ki-duotone ki-cross fs-6 me-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>{{ __('translation.clear_filters') }}
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form id="filterForm">
                    <div class="row">
                        <!-- Country Filter -->
                        <div class="col-md-3 mb-3">
                            <label for="country_filter" class="form-label fw-bold">{{ __('translation.country') }}</label>
                            <select class="form-select" id="country_filter" name="country">
                                <option value="">{{ __('translation.all_countries') }}</option>
                                @foreach(\App\Models\Country::all() as $country)
                                    <option value="{{ $country->id }}">{{ $country->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Date From Filter -->
                        <div class="col-md-3 mb-3">
                            <label for="date_from" class="form-label fw-bold">{{ __('translation.from_date') }}</label>
                            <input type="date" class="form-control" id="date_from" name="date_from">
                        </div>
                        
                        <!-- Date To Filter -->
                        <div class="col-md-3 mb-3">
                            <label for="date_to" class="form-label fw-bold">{{ __('translation.to_date') }}</label>
                            <input type="date" class="form-control" id="date_to" name="date_to">
                        </div>
                        
                        <!-- Search Filter -->
                        <div class="col-md-3 mb-3">
                            <label for="search_filter" class="form-label fw-bold">{{ __('translation.search') }}</label>
                            <input type="text" class="form-control" id="search_filter" name="search" placeholder="{{ __('translation.search_customers') }}">
                        </div>
                    </div>
                    
                </form>
            </div>
        </div>

        <div class="card">
            <!--begin::Card header-->
            <div class="card-header border-0 pt-6">
                <!--begin::Card title-->
                <div class="card-title">
                    <h3 class="fw-bold">{{ __('translation.customers_list') }}</h3>
                </div>
                <!--end::Card title-->
            </div>
            <!--end::Card header-->
            
            <!--begin::Card body-->
            <div class="card-body pt-0">
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
                
                <!--begin::Filter Card-->
                
                <!--end::Filter Card-->
                
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5" id="customers-table">
                        <thead>
                            <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                                <th>{{ __('translation.customer') }}</th>
                                <th>{{ __('translation.address') }}</th>
                                <th>{{ __('translation.created_at') }}</th>
                                <th class="text-end">{{ __('translation.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Customers will be loaded here via DataTable -->
                        </tbody>
                    </table>
                </div>
            </div>
            <!--end::Card body-->
        </div>
        <!--end::Card-->
    </div>
</div>

<!--begin::Import Customers Modal-->
<div class="modal fade" id="importCustomersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Import Customers</h5>
                <button type="button" class="btn btn-sm btn-icon" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ki-duotone ki-cross fs-2x"></i>
                </button>
            </div>
            <form id="importCustomersForm" action="{{ route('merchant.customers.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info d-flex align-items-start">
                        <i class="ki-duotone ki-information-2 fs-2x me-3"></i>
                        <div>
                            <div class="fw-bold mb-2">Expected file columns</div>
                            <div class="text-gray-800">name, email, address, country, state, zip</div>
                            <div class="mt-3">
                                <a href="{{ route('merchant.customers.export-template') }}">Download sample file</a>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="customers_file" class="form-label required">Select file</label>
                        <input type="file" class="form-control" id="customers_file" name="file" accept=".xlsx,.xls,.csv" required>
                        <div class="form-text">Allowed types: .xlsx, .xls, .csv</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="previewImportBtn" class="btn btn-primary">Preview</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!--end::Import Customers Modal-->

<!--begin::Preview Modal-->
<div class="modal fade" id="previewCustomersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Import Preview</h5>
                <button type="button" class="btn btn-sm btn-icon" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ki-duotone ki-cross fs-2x"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-5">Rows with unresolved country or duplicate email will be skipped.</div>
                <div class="table-responsive">
                    <table class="table table-striped align-middle" id="previewTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>name</th>
                                <th>email</th>
                                <th>address</th>
                                <th>country</th>
                                <th>state</th>
                                <th>zip</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" id="confirmImportBtn" class="btn btn-success">Confirm Import</button>
            </div>
        </div>
    </div>
</div>
<!--end::Preview Modal-->

@endsection


@push('scripts')
<script>
    let customersTable = $('#customers-table').DataTable({
        dom: "tiplr",
        serverSide: false,
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
            url: '{{ route("merchant.customers.data") }}',
            data: function(d) {
                d.country = $('#country_filter').val();
                d.date_from = $('#date_from').val();
                d.date_to = $('#date_to').val();
                d.search = $('#search_filter').val();
            },
            error: function (xhr, error, thrown) {
                console.error('{{ __("translation.data_table_error") }}:', error);
                toastr.error('{{ __("translation.something_went_wrong") }}');
            }
        },
        columns: [
            {
                data: null,
                name: 'name',
                render: function (data, type, row) {
                    const name = row.name || '';
                    const email = row.email || '';
                    const phone = row.phone || '';
                    const initial = name.trim().charAt(0).toUpperCase();
                    return `
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-35px symbol-circle me-3">
                                <span class="symbol-label fw-bold bg-light-primary text-primary">${initial || 'C'}</span>
                            </div>
                            <div class="d-flex flex-column">
                                <span class="fw-bold text-gray-800">${name}</span>
                                <span class="text-muted fs-7">${email}</span>
                                <span class="text-muted fs-7">${phone}</span>
                            </div>
                        </div>`;
                }
            },
            {
                data: 'address_info',
                name: 'address_info'
            },
            {
                data: 'created_at',
                name: 'created_at'
            },
            {
                data: 'actions',
                name: 'actions',
                searchable: false,
                sortable: false,
                width: '20%'
            }
        ],
        order: [[0, 'asc']],
        drawCallback: function (settings) {
            // Re-initialize KTMenu dropdowns here
            if (typeof KTMenu !== 'undefined' && typeof KTMenu.createInstances === 'function') {
                KTMenu.createInstances();
            }
        }
    });

    // Refresh table functionality
    $('#refresh-table').on('click', function() {
        $(this).addClass('spinner spinner-sm spinner-left');
        customersTable.ajax.reload(function() {
            $('#refresh-table').removeClass('spinner spinner-sm spinner-left');
        });
    });





    // Delete Customer
    $(document).on('click', '.delete-customer', function() {
        var customerId = $(this).data('id');
        var customerName = $(this).data('name');
        
        if (confirm('{{ __("translation.are_you_sure_delete_customer") }}: ' + customerName + '{{ __("translation.question_mark") }}')) {
            $.ajax({
                url: '/merchant/customers/' + customerId,
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

    // Filter toggle functionality
    $('#filter-toggle').on('click', function() {
        $('#filter-card').slideToggle();
    });

    // Auto-apply filters when any field changes
    $('#country_filter, #date_from, #date_to').on('change', function() {
        customersTable.ajax.reload();
    });

    // Auto-apply search filter with debounce (wait 500ms after user stops typing)
    let searchTimeout;
    $('#search_filter').on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            customersTable.ajax.reload();
        }, 500);
    });

    // Clear filters functionality
    $('#clearFilters').on('click', function() {
        $('#country_filter').val('');
        $('#date_from').val('');
        $('#date_to').val('');
        $('#search_filter').val('');
        customersTable.ajax.reload();
    });

    // Date validation - ensure from_date is not greater than to_date
    $('#date_from, #date_to').on('change', function() {
        const fromDate = $('#date_from').val();
        const toDate = $('#date_to').val();
        
        if (fromDate && toDate && fromDate > toDate) {
            toastr.warning('{{ __("translation.from_date_cannot_be_greater_than_to_date") }}');
            $(this).val('');
        }
    });

    // Export functionality
    $('#export-customers').on('click', function() {
        const country = $('#country_filter').val();
        const dateFrom = $('#date_from').val();
        const dateTo = $('#date_to').val();
        const search = $('#search_filter').val();
        
        // Build export URL with current filter parameters
        let exportUrl = '{{ route("merchant.customers.export") }}?';
        const params = new URLSearchParams();
        
        if (country) params.append('country', country);
        if (dateFrom) params.append('date_from', dateFrom);
        if (dateTo) params.append('date_to', dateTo);
        if (search) params.append('search', search);
        
        exportUrl += params.toString();
        
        // Show loading state
        $(this).addClass('spinner spinner-sm spinner-left');
        
        // Create a temporary link to trigger download
        const link = document.createElement('a');
        link.href = exportUrl;
        link.download = '';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        // Remove loading state
        setTimeout(() => {
            $(this).removeClass('spinner spinner-sm spinner-left');
        }, 1000);
    });

    // Store selected file for import
    let selectedImportFile = null;

    // Import preview flow
    $(document).on('click', '#previewImportBtn', function(e){
        e.preventDefault();
        var fileInput = document.getElementById('customers_file');
        if (!fileInput.files || !fileInput.files[0]) {
            alert('Please choose a file first.');
            return;
        }
        // Store the file for later use in confirm import
        selectedImportFile = fileInput.files[0];
        console.log('File stored for import:', selectedImportFile);
        
        var formData = new FormData();
        formData.append('file', selectedImportFile);
        formData.append('_token', '{{ csrf_token() }}');

        $('#previewImportBtn').prop('disabled', true).text('Loading...');

        $.ajax({
            url: '{{ route('merchant.customers.import-preview') }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(resp){
                $('#previewImportBtn').prop('disabled', false).text('Preview');
                if (!resp.success) {
                    alert(resp.message || 'Failed to parse file');
                    return;
                }
                var tbody = $('#previewTable tbody');
                tbody.empty();
                resp.rows.forEach(function(row, idx){
                    var flags = row.flags || {};
                    var okCountry = flags.countryResolved;
                    var dup = flags.duplicateEmail;
                    var statusParts = [];
                    if (!okCountry) statusParts.push('No country match');
                    if (dup) statusParts.push('Duplicate email');
                    if (statusParts.length === 0) statusParts.push('Ready');
                    var tr = '<tr>'+
                        '<td>'+(idx+1)+'</td>'+
                        '<td>'+(row.original.name||'')+'</td>'+
                        '<td>'+(row.original.email||'')+'</td>'+
                        '<td>'+(row.original.address||'')+'</td>'+
                        '<td>'+(row.original.country||'')+'</td>'+
                        '<td>'+(row.original.state||'')+'</td>'+
                        '<td>'+(row.original.zip||'')+'</td>'+
                        '<td>'+(statusParts.join(', '))+'</td>'+
                    '</tr>';
                    tbody.append(tr);
                });
                var importModalEl = document.getElementById('importCustomersModal');
                var importModal = bootstrap.Modal.getOrCreateInstance(importModalEl);
                importModal.hide();
                var previewModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('previewCustomersModal'));
                previewModal.show();
            },
            error: function(xhr){
                $('#previewImportBtn').prop('disabled', false).text('Preview');
                var msg = 'Failed to parse file';
                try { var r = JSON.parse(xhr.responseText); if (r.message) msg = r.message; } catch(e){}
                alert(msg);
            }
        });
    });

    // Confirm import: submit the import form with the selected file
    $(document).on('click', '#confirmImportBtn', function(e){
        e.preventDefault();
        var $btn = $(this);
        console.log('Confirm import clicked, selectedImportFile:', selectedImportFile);
        if (!selectedImportFile) {
            alert('Please choose a file first.');
            return;
        }
        
        // Create a new FormData and append the stored file
        var formData = new FormData();
        formData.append('file', selectedImportFile);
        formData.append('_token', '{{ csrf_token() }}');
        
        $btn.prop('disabled', true).text('Importing...');
        
        // Submit via AJAX instead of form submit
        $.ajax({
            url: '{{ route('merchant.customers.import') }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response){
                $btn.prop('disabled', false).text('Confirm Import');
                var previewModal = bootstrap.Modal.getInstance(document.getElementById('previewCustomersModal'));
                if (previewModal) previewModal.hide();
                
                // Show success message and reload table
                toastr.success('Customers imported successfully');
                customersTable.ajax.reload();
                
                // Reset the file input and stored file
                document.getElementById('customers_file').value = '';
                selectedImportFile = null;
            },
            error: function(xhr){
                $btn.prop('disabled', false).text('Confirm Import');
                var msg = 'Failed to import customers';
                try { 
                    var r = JSON.parse(xhr.responseText); 
                    if (r.message) msg = r.message; 
                } catch(e){}
                toastr.error(msg);
            }
        });
    });
</script>
@endpush
