@extends('layouts.admin.admin_layout')

@section('main-head' , __('translation.edit_merchant'))

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
        <li class="breadcrumb-item text-muted">{{ __('translation.merchants') }}</li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">{{ __('translation.edit_merchant') }}</li>
        <!--end::Item-->
    </ul>
    <!--end::Breadcrumb-->
@endsection

@section('toolbar_actions')
<div class="d-flex align-items-center gap-2 gap-lg-3">
    <div class="m-0">
        <a href="{{ route('merchants.index') }}" class="btn btn-light-danger btn-sm me-3">
            <i class="ki-duotone ki-arrow-left fs-2">
                <span class="path1"></span>
                <span class="path2"></span>
            </i>
            {{ __('translation.back') }}
        </a>
    </div>
</div>
@endsection

@section('content')

    <div class="post d-flex flex-column-fluid" id="kt_post">
        <!--begin::Container-->
        <div id="kt_content_container" class="container-xxl">
            <form action="{{route('merchants.update', $merchant)}}" method="post" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="row col-md-9">
                        <div class="card ">
                            <div class="card-header border-0 ">
                                <!--begin::Card title-->
                                <div class="card-title">
                                    <h2>{{ __('translation.edit_merchant') }}: {{ $merchant->name }}</h2>
                                </div>
                                <!--begin::Card toolbar-->
                                <div class="card-toolbar">
                                    <!--begin::Toolbar-->
                                    {{-- <div class="d-flex justify-content-end" data-kt-roles-table-toolbar="base">
                                        <a href="{{ route('merchants.index') }}" class="btn btn-light-danger me-3">
                                            <i class="ki-duotone ki-arrow-left fs-2">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                            {{ __('translation.back') }}
                                        </a>
                                    </div>
                                    <!--end::Toolbar--> --}}
                                </div>
                                <!--end::Card toolbar-->
                            </div>
                            
                            <div class="card-body p-3">
                                <div class="col-md-12">
                                    <div class="">
                                        <!-- General Validation Errors -->
                                        @if ($errors->any())
                                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                                <div class="d-flex">
                                                    <i class="ki-duotone ki-cross-circle fs-2hx text-danger me-4">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>
                                                    <div class="d-flex flex-column">
                                                        <h4 class="mb-1">{{ __('translation.validation_errors') }}</h4>
                                                        <ul class="mb-0">
                                                            @foreach ($errors->all() as $error)
                                                                <li>{{ $error }}</li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                </div>
                                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                            </div>
                                        @endif
                                        
                                        <div class="row">
                                            <!-- Merchant Information -->
                                            <div class="col-12">
                                                <h4 class="mb-3">{{ __('translation.merchant_information') }}</h4>
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label for="name" class="form-label">{{ __('translation.business_name') }} <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                                       id="business_name" name="business_name" value="{{old('business_name', $merchant->business_name)}}" 
                                                       placeholder="{{ __('translation.business_name') }}" required>
                                                @error('name')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label for="owner_name" class="form-label">{{ __('translation.owner_name') }} <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control @error('owner_name') is-invalid @enderror" 
                                                       id="owner_name" name="owner_name" value="{{old('owner_name', $merchant->owner_name)}}" 
                                                       placeholder="{{ __('translation.owner_name') }}" required>
                                                @error('owner_name')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label for="email" class="form-label">{{ __('translation.email') }} <span class="text-danger">*</span></label>
                                                <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                                       id="email" name="email" value="{{old('email', $merchant->email)}}" 
                                                       placeholder="{{ __('translation.email') }}" required>
                                                @error('email')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label for="phone" class="form-label">{{ __('translation.phone_number') }} <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control @error('phone') is-invalid @enderror" 
                                                       id="phone" name="phone" value="{{old('phone', $merchant->phone)}}" 
                                                       placeholder="{{ __('translation.phone_number') }}" required>
                                                @error('phone')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label for="business_type" class="form-label">{{ __('translation.business_type') }} <span class="text-danger">*</span></label>
                                                <select class="form-select @error('business_type') is-invalid @enderror" 
                                                        id="business_type" name="business_type" required>
                                                    <option value="">{{ __('translation.select_business_type') }}</option>
                                                    @foreach(\App\Enums\BusinessType::toArray() as $value => $label)
                                                        <option value="{{ $value }}" {{old('business_type', $merchant->business_type?->value) == $value ? 'selected' : ''}}>
                                                            {{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('business_type')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            
                                            <div class="col-md-12 mb-3">
                                                <label for="address" class="form-label">{{ __('translation.business_address') }} <span class="text-danger">*</span></label>
                                                <textarea class="form-control @error('address') is-invalid @enderror" 
                                                          id="address" name="address" rows="3" 
                                                          placeholder="{{ __('translation.business_address') }}" required>{{old('address', $merchant->address)}}</textarea>
                                                @error('address')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            
                                            <!-- Country -->
                                            <div class="col-md-6 mb-3">
                                                <label for="country_id" class="form-label">{{ __('translation.country') }} <span class="text-danger">*</span></label>
                                                <select class="form-select @error('country_id') is-invalid @enderror" id="country_id" name="country_id" data-placeholder="{{ __('translation.select_country') }}" required>
                                                    @if(old('country_id', $merchant->country_id))
                                                        <option value="{{ old('country_id', $merchant->country_id) }}" selected>{{ $merchant->country?->name }}</option>
                                                    @else
                                                        <option value="">{{ __('translation.select_country') }}</option>
                                                    @endif
                                                </select>
                                                @error('country_id')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <!-- City -->
                                            <div class="col-md-6 mb-3">
                                                <label for="city_id" class="form-label">{{ __('translation.city') }} <span class="text-danger">*</span></label>
                                                <select class="form-select @error('city_id') is-invalid @enderror" id="city_id" name="city_id" data-placeholder="{{ __('translation.select_city') }}" required {{ old('country_id', $merchant->country_id) ? '' : 'disabled' }}>
                                                    @if(old('city_id', $merchant->city_id))
                                                        <option value="{{ old('city_id', $merchant->city_id) }}" selected>{{ $merchant->city?->name }}</option>
                                                    @else
                                                        <option value="">{{ __('translation.select_city') }}</option>
                                                    @endif
                                                </select>
                                                @error('city_id')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label for="is_active" class="form-label">{{ __('translation.status') }} <span class="text-danger">*</span></label>
                                                <select class="form-select @error('is_active') is-invalid @enderror" 
                                                        id="is_active" name="is_active" required>
                                                    <option value="1" {{old('is_active', $merchant->is_active) == '1' ? 'selected' : ''}}>Active</option>
                                                    <option value="0" {{old('is_active', $merchant->is_active) == '0' ? 'selected' : ''}}>Inactive</option>
                                                </select>
                                                @error('is_active')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <!-- Trade License Number -->
                                            <div class="col-md-6 mb-3">
                                                <label for="trade_license_number" class="form-label">{{ __('translation.trade_license_number') }} <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control @error('trade_license_number') is-invalid @enderror" 
                                                       id="trade_license_number" name="trade_license_number" value="{{ old('trade_license_number', $merchant->trade_license_number) }}" 
                                                       placeholder="{{ __('translation.trade_license_number') }}" required>
                                                @error('trade_license_number')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                        
                                            <!-- Tax Certified Number -->
                                            <div class="col-md-6 mb-3">
                                                <label for="tax_certified_number" class="form-label">{{ __('translation.tax_certified_number') }} <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control @error('tax_certified_number') is-invalid @enderror" 
                                                       id="tax_certified_number" name="tax_certified_number" value="{{ old('tax_certified_number', $merchant->tax_number) }}" 
                                                       placeholder="{{ __('translation.tax_certified_number') }}" required>
                                                @error('tax_certified_number')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <!-- Trade License Start Date -->
                                            <div class="col-md-6 mb-3">
                                                <label for="trade_license_start_date" class="form-label">{{ __('translation.trade_license_start_date') }} <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control @error('trade_license_start_date') is-invalid @enderror" 
                                                       id="trade_license_start_date" name="trade_license_start_date" value="{{ old('trade_license_start_date', optional($merchant->trade_license_start_date)->format('Y-m-d')) }}" required>
                                                @error('trade_license_start_date')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <!-- Trade License Expired Date -->
                                            <div class="col-md-6 mb-3">
                                                <label for="trade_license_expired_date" class="form-label">{{ __('translation.trade_license_expired_date') }} <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control @error('trade_license_expired_date') is-invalid @enderror" 
                                                       id="trade_license_expired_date" name="trade_license_expired_date" value="{{ old('trade_license_expired_date', optional($merchant->trade_license_expired_date)->format('Y-m-d')) }}" required>
                                                @error('trade_license_expired_date')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            
                                            <!-- Merchant Code (Read-only) -->
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">{{ __('translation.merchant_code') }}</label>
                                                <input type="text" class="form-control" value="{{ $merchant->merchant_code }}" readonly>
                                                <small class="text-muted">{{ __('translation.this_code_is_automatically_generated') }}</small>
                                            </div>
                                            
                                            {{-- <!-- User Account Information -->
                                            <div class="col-12 mt-4">
                                                <h4 class="mb-3">{{ __('translation.user_account') }}</h4>
                                                @if($merchant->user)
                                                    <div class="alert alert-info">
                                                        <strong>{{ __('translation.linked_user') }}:</strong> {{ $merchant->user->name }} ({{ $merchant->user->email }})
                                                        <br>
                                                        <small>{{ __('translation.id') }}: {{ $merchant->user->id }}</small>
                                                    </div>
                                                @else
                                                    <div class="alert alert-info">
                                                        <strong>{{ __('translation.user_account_auto_created') }}</strong>
                                                        <br>
                                                        <small>{{ __('translation.user_account_will_be_created_automatically') }}</small>
                                                    </div>
                                                @endif
                                            </div> --}}
                                        </div>
                                    </div><!-- end of tile -->
                                </div><!-- end of col -->
                            </div>
                            <div class="mt-10 card-footer">
                                                <button class="btn-primary btn">
                                                    {{ __('translation.update_merchant') }}
                                                </button>
                                                <a href="#" onclick="window.history.back()"
                                                   class="btn btn-light-danger">
                                                    {{ __('translation.cancel') }}
                                                </a>
                                            </div>
                        </div>

                    </div><!-- en of row -->
                    <div class="col-md-3">
                        <div class="card-body p-3">
                            <div class="col-md-12">
                                <div class="card p-4">
                                    {{-- <label for="logo_file" class="form-label">{{ __('translation.merchant_logo') }}</label> --}}
                                    <x:image-picker class="col-md-12" name="merchant_logo" filed-name='logo'
                                                    real-filed-id="logo_file" :current-image="$merchant->logo_url"/>
                                    <input type="file" name="logo" id="logo_file" class="d-none @error('logo') is-invalid @enderror" accept="image/*">
                                    @error('logo')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">{{ __('translation.upload_merchant_logo') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </form>
        </div>
    </div>

@endsection

@push("scripts")
    <script>
        $(document).ready(function() {
            const countrySelect = $('#country_id');
            const citySelect = $('#city_id');

            if (countrySelect.length) {
                countrySelect.select2({
                    ajax: {
                        url: '{{ route("country.select") }}',
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return { search: params.term };
                        },
                        processResults: function (data) {
                            const items = (data.data || data) || [];
                            return { results: items.map(c => ({ id: c.id, text: c.text })) };
                        },
                        cache: true
                    },
                    placeholder: '{{ __('translation.select_country') }}',
                    allowClear: true,
                    width: '100%'
                });
            }

            function initCities(countryId) {
                citySelect.prop('disabled', !countryId);
                citySelect.val(null).trigger('change');
                if (!countryId) return;
                citySelect.select2({
                    ajax: {
                        url: '{{ route("city.select") }}',
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return { search: params.term, country_id: countryId };
                        },
                        processResults: function (data) {
                            const items = (data.data || data) || [];
                            return { results: items.map(c => ({ id: c.id, text: c.text })) };
                        },
                        cache: true
                    },
                    placeholder: '{{ __('translation.select_city') }}',
                    allowClear: true,
                    width: '100%'
                });
            }

            countrySelect.on('change', function(){
                initCities($(this).val());
            });

            const initialCountryId = '{{ old('country_id', $merchant->country_id) }}';
            const initialCityId = '{{ old('city_id', $merchant->city_id) }}';
            if (initialCountryId) {
                initCities(initialCountryId);
                if (initialCityId) {
                    const initialCityText = @json(optional($merchant->city)->name);
                    if (initialCityText) {
                        citySelect.append(new Option(initialCityText, initialCityId, true, true)).trigger('change');
                    }
                }
            }
            // Auto-hide validation errors after 5 seconds
            setTimeout(function() {
                $('.alert-danger').fadeOut('slow');
            }, 5000);

            // Clear validation errors when user starts typing
            $('input, select, textarea').on('input change', function() {
                $(this).removeClass('is-invalid');
                $(this).siblings('.invalid-feedback').hide();
            });

            // Form validation on submit
            $('form').on('submit', function() {
                let hasErrors = false;
                
                // Check required fields
                $(this).find('input[required], select[required], textarea[required]').each(function() {
                    if (!$(this).val()) {
                        $(this).addClass('is-invalid');
                        hasErrors = true;
                    }
                });

                if (hasErrors) {
                    // Scroll to first error
                    $('html, body').animate({
                        scrollTop: $('.is-invalid:first').offset().top - 100
                    }, 500);
                    return false;
                }
            });
        });
    </script>
@endpush 