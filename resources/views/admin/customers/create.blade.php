@extends('layouts.admin.admin_layout')

@section('main-head', __('translation.add_customer'))

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
        <li class="breadcrumb-item text-muted">{{ __('translation.add_customer') }}</li>
    </ul>
@endsection

@section('toolbar_actions')
<div class="d-flex align-items-center gap-2 gap-lg-3">
    <!--begin::Back button-->
    <a href="{{ route('admin.customers.index') }}" class="btn btn-sm btn-flex btn-light fw-bold">
        <i class="ki-duotone ki-arrow-left fs-6 text-muted me-1">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
        {{ __('translation.back') }}
    </a>
    <!--end::Back button-->
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
                <form id="addCustomerForm" action="{{ route('admin.customers.store') }}" method="POST">
                    @csrf
                    
                    <div class="row">
                        <!-- Merchant Selection -->
                        <x:select2-input class="col-md-6" name="merchant" filed-name="merchant_id"
                        url="{{route('merchants.select')}}" />

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

                        <div class="col-md-6 mb-3">
                            <label for="country_id" class="form-label">{{ __('translation.country') }}</label>
                            <select class="form-select @error('country_id') is-invalid @enderror" id="country_id" name="country_id" data-placeholder="{{ __('translation.select_country') }}">
                                <option value="">{{ __('translation.select_country') }}</option>
                            </select>
                            @error('country_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- City -->
                        <div class="col-md-6 mb-3">
                            <label for="city_id" class="form-label">{{ __('translation.city') }}</label>
                            <select class="form-select @error('city_id') is-invalid @enderror" id="city_id" name="city_id" data-placeholder="{{ __('translation.select_city') }}" disabled>
                                <option value="">{{ __('translation.select_city') }}</option>
                            </select>
                            @error('city_id')
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

                       

                        <!-- State -->
                        <div class="col-md-6 mb-3">
                            <label for="customer_state" class="form-label">{{ __('translation.state') }}</label>
                            <input type="text" class="form-control @error('state') is-invalid @enderror" 
                                   id="customer_state" name="state" value="{{ old('state') }}">
                            @error('state')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- ZIP -->
                        <div class="col-md-6 mb-3">
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
                        <a href="{{ route('admin.customers.index') }}" class="btn btn-secondary">
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
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Initialize Select2 for merchant selection
      // Load countries
      $('#country_id').select2({
        ajax: {
            url: '{{ url('/country-select') }}',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { search: params.term };
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
                    return { search: params.term, country_id: countryId };
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

        // Form submission handling
      

      
    });
</script>
@endpush
