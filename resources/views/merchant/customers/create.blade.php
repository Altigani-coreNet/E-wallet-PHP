@extends('layouts.merchant.merchant_layout')

@section('main-head', __('translation.add_customer'))

@section('breadcrumbs')
    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
        <li class="breadcrumb-item text-muted">
            <a href="{{ route('merchant.dashboard') }}" class="text-muted text-hover-primary">{{ __('translation.home') }}</a>
        </li>
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <li class="breadcrumb-item text-muted">
            <a href="{{ route('merchant.customers.index') }}" class="text-muted text-hover-primary">{{ __('translation.customers') }}</a>
        </li>
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <li class="breadcrumb-item text-muted">{{ __('translation.add_customer') }}</li>
    </ul>
@endsection

@section('toolbar_actions')
<div class="d-flex align-items-center gap-2 gap-lg-3">
    <!--begin::Back button-->
    <a href="{{ route('merchant.customers.index') }}" class="btn btn-sm btn-flex btn-light fw-bold">
        <i class="ki-duotone ki-arrow-left fs-6 text-muted me-1">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
        {{ __('translation.back') }}
    </a>
    <!--end::Back button-->
    
    <!--begin::Import Customers button-->
    <button type="button" class="btn btn-sm btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#importCustomersModal">
        <i class="ki-duotone ki-upload fs-6 me-1">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
        Import Customers
    </button>
    <!--end::Import Customers button-->
</div>
@endsection

@section('content')
<div class="post d-flex flex-column-fluid" id="kt_post">
    <div id="kt_content_container" class="container-xxl">
        <!--begin::Card-->
        <div class="card">
            <!--begin::Card header-->
            <div class="card-header border-0 pt-6">
                <!--begin::Card title-->
                <div class="card-title">
                    <h3 class="fw-bold">{{ __('translation.add_customer') }}</h3>
                </div>
                <!--end::Card title-->
            </div>
            <!--end::Card header-->
            
            <!--begin::Card body-->
            <div class="card-body pt-0">
                <form id="addCustomerForm" action="{{ route('merchant.customers.store') }}" method="POST">
                    @csrf
                    
                    <div class="row">
                        <!-- Customer Name -->
                        <div class="col-md-6 mb-3">
                            <label for="customer_name" class="form-label required">{{ __('translation.customer_name') }}</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="customer_name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Phone -->
                        <div class="col-md-6 mb-3">
                            <label for="customer_phone" class="form-label required">{{ __('translation.phone') }}</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" 
                                   id="customer_phone" name="phone" value="{{ old('phone') }}" required>
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div class="col-md-6 mb-3">
                            <label for="customer_email" class="form-label required">{{ __('translation.email') }}</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                   id="customer_email" name="email" value="{{ old('email') }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Address -->
                        <div class="col-md-6 mb-3">
                            <label for="customer_address" class="form-label">{{ __('translation.address') }}</label>
                            <input type="text" class="form-control @error('address') is-invalid @enderror" 
                                   id="customer_address" name="address" value="{{ old('address') }}">
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Country -->
                        <div class="col-md-4 mb-3">
                            <label for="country_id" class="form-label">{{ __('translation.country') }}</label>
                            <select class="form-select @error('country_id') is-invalid @enderror" id="country_id" name="country_id" data-placeholder="{{ __('translation.select_country') }}">
                                <option value="">{{ __('translation.select_country') }}</option>
                            </select>
                            @error('country_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- City -->
                        <div class="col-md-4 mb-3">
                            <label for="city_id" class="form-label">{{ __('translation.city') }}</label>
                            <select class="form-select @error('city_id') is-invalid @enderror" id="city_id" name="city_id" data-placeholder="{{ __('translation.select_city') }}" disabled>
                                <option value="">{{ __('translation.select_city') }}</option>
                            </select>
                            @error('city_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- State -->
                        <div class="col-md-4 mb-3">
                            <label for="customer_state" class="form-label">{{ __('translation.state') }}</label>
                            <input type="text" class="form-control @error('state') is-invalid @enderror" 
                                   id="customer_state" name="state" value="{{ old('state') }}">
                            @error('state')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- ZIP -->
                        <div class="col-md-4 mb-3">
                            <label for="customer_zip" class="form-label">{{ __('translation.zip') }}</label>
                            <input type="text" class="form-control @error('zip') is-invalid @enderror" 
                                   id="customer_zip" name="zip" value="{{ old('zip') }}">
                            @error('zip')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-end gap-2 mt-6">
                        <a href="{{ route('merchant.customers.index') }}" class="btn btn-secondary">
                            {{ __('translation.cancel') }}
                        </a>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <span class="indicator-label">{{ __('translation.save_customer') }}</span>
                            <span class="indicator-progress">
                                {{ __('translation.please_wait') }}
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
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
                            <div class="text-gray-800">name, email, address, country, state, zip , 
                                <a href="{{ route('merchant.customers.export-template') }}">Download sample file</a>
                            </div>
                            {{-- <div class="mt-3">
                            </div> --}}
                        </div>
                    </div>

                    <!-- Optional helper: country list reference -->
                    

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
    <!-- properly close import modal container -->
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
    // Form submission handling
    $('#addCustomerForm').on('submit', function(e) {
        // Allow regular form submission
        var $submitBtn = $('#submitBtn');
        
        // Show loading state
        $submitBtn.attr('data-kt-indicator', 'on');
        $submitBtn.prop('disabled', true);
    });

    // Remove validation errors on input
    $('input').on('input', function() {
        $(this).removeClass('is-invalid');
        $(this).siblings('.invalid-feedback').remove();
    });

    // Load countries
    $('#country_id').select2({
        ajax: {
            url: '{{ url('/country-select') }}',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { q: params.term };
            },
            processResults: function (data) {
                return { results: data };
            },
            cache: true
        },
        placeholder: $('#country_id').data('placeholder'),
        minimumInputLength: 0,
        width: '100%'
    });

    // Load cities depending on country
    function initCities(countryId) {
        $('#city_id').prop('disabled', !countryId);
        $('#city_id').val(null).trigger('change');
        if (!countryId) return;
        $('#city_id').select2({
            ajax: {
                url: '{{ url('/city-select') }}',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term, country_id: countryId };
                },
                processResults: function (data) {
                    return { results: data };
                },
                cache: true
            },
            placeholder: $('#city_id').data('placeholder'),
            minimumInputLength: 0,
            width: '100%'
        });
    }

    $('#country_id').on('change', function(){
        initCities($(this).val());
    });

    // Country reference select2 in import modal
    $('#import_country_reference').select2({
        ajax: {
            url: '{{ url('/country-select') }}',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { q: params.term };
            },
            processResults: function (data) {
                return { results: data };
            },
            cache: true
        },
        placeholder: $('#import_country_reference').data('placeholder') || 'Search countries...',
        minimumInputLength: 0,
        width: '100%'
    });

    // Store selected file for import
    let selectedImportFile = null;

    // Preview flow: submit file via AJAX to preview endpoint, render modal table
    $('#previewImportBtn').on('click', function(e){
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
    $('#confirmImportBtn').on('click', function(e){
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
                
                // Show success message and redirect
                toastr.success('Customers imported successfully');
                setTimeout(function(){
                    window.location.href = '{{ route('merchant.customers.index') }}';
                }, 1500);
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
