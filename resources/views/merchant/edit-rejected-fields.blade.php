@extends('layouts.merchant.merchant_layout')
@section('main-head', __('translation.edit_rejected_fields'))
@section('page_title', __('translation.edit_rejected_fields'))

@section('breadcrumb')
<!--begin::Item-->
<li class="breadcrumb-item text-muted">
    <a href="{{ route('merchant.profile') }}" class="text-muted text-hover-primary">{{ __('translation.profile') }}</a>
</li>
<!--end::Item-->
<!--begin::Item-->
<li class="breadcrumb-item">
    <span class="bullet bg-gray-400 w-5px h-2px"></span>
</li>
<!--end::Item-->
<!--begin::Item-->
<li class="breadcrumb-item text-muted">{{ __('translation.edit_rejected_fields') }}</li>
<!--end::Item-->
@endsection

@section('content')
<div class="post d-flex flex-column-fluid" id="kt_post">
    <!--begin::Container-->
    <div id="kt_content_container" class="container-xxl">
        <div class="row">
            <!-- Rejection Information Card -->
            <div class="col-xl-12">
                <div class="card mb-5 mb-xl-10">
                    <div class="card-header">
                        <div class="card-title">
                            <h3 class="fw-bolder m-0">{{ __('translation.rejection_details') }}</h3>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <h4 class="alert-heading">{{ __('translation.rejection_reason') }}</h4>
                            <p>{{ $rejection->rejection_reason }}</p>
                        </div>
                        
                        @if($rejection->invalid_fields && count($rejection->invalid_fields) > 0)
                        <div class="alert alert-danger">
                            <h4 class="alert-heading">{{ __('translation.invalid_fields') }}</h4>
                            <ul class="mb-0">
                                @foreach($rejection->invalid_fields as $field)
                                    <li>{{ __('translation.' . $field) }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                        @if($rejection->missing_attachments && count($rejection->missing_attachments) > 0)
                        <div class="alert alert-info">
                            <h4 class="alert-heading">{{ __('translation.missing_attachments') }}</h4>
                            <ul class="mb-0">
                                @foreach($rejection->missing_attachments as $attachment)
                                    <li>{{ __('translation.' . $attachment) }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Basic Information Card - Only Rejected Fields -->
            <div class="col-xl-12">
                <div class="card mb-5 mb-xl-10">
                    <div class="card-header">
                        <div class="card-title">
                            <h3 class="fw-bolder m-0">{{ __('translation.edit_rejected_fields') }}</h3>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('merchant.update.rejected_fields', $merchant->id) }}" method="POST" enctype="multipart/form-data" id="edit_merchant_form">
                            @csrf
                            @method('PUT')
                            
                            @if($rejection->invalid_fields && in_array('name', $rejection->invalid_fields))
                            <div class="row mb-6">
                                <label class="col-lg-4 col-form-label required fw-bold fs-6">{{ __('translation.business_name') }}</label>
                                <div class="col-lg-8">
                                    <input type="text" name="name" class="form-control form-control-lg form-control-solid @error('name') is-invalid @enderror" 
                                           placeholder="{{ __('translation.business_name') }}" value="{{ old('name', $merchant->name) }}" />
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            @endif

                            @if($rejection->invalid_fields && in_array('owner_name', $rejection->invalid_fields))
                            <div class="row mb-6">
                                <label class="col-lg-4 col-form-label required fw-bold fs-6">{{ __('translation.owner_name') }}</label>
                                <div class="col-lg-8">
                                    <input type="text" name="owner_name" class="form-control form-control-lg form-control-solid @error('owner_name') is-invalid @enderror" 
                                           placeholder="{{ __('translation.owner_name') }}" value="{{ old('owner_name', $merchant->owner_name) }}" />
                                    @error('owner_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            @endif

                            @if($rejection->invalid_fields && in_array('email', $rejection->invalid_fields))
                            <div class="row mb-6">
                                <label class="col-lg-4 col-form-label required fw-bold fs-6">{{ __('translation.merchant_email') }}</label>
                                <div class="col-lg-8">
                                    <input type="email" name="email" class="form-control form-control-lg form-control-solid @error('email') is-invalid @enderror" 
                                           placeholder="{{ __('translation.merchant_email') }}" value="{{ old('email', $merchant->email) }}" />
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            @endif

                            @if($rejection->invalid_fields && in_array('phone', $rejection->invalid_fields))
                            <div class="row mb-6">
                                <label class="col-lg-4 col-form-label required fw-bold fs-6">{{ __('translation.merchant_phone') }}</label>
                                <div class="col-lg-8">
                                    <input type="text" name="phone" class="form-control form-control-lg form-control-solid @error('phone') is-invalid @enderror" 
                                           placeholder="{{ __('translation.merchant_phone') }}" value="{{ old('phone', $merchant->phone) }}" />
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            @endif

                            @if($rejection->invalid_fields && in_array('business_type', $rejection->invalid_fields))
                            <div class="row mb-6">
                                <label class="col-lg-4 col-form-label required fw-bold fs-6">{{ __('translation.business_type') }}</label>
                                <div class="col-lg-8">
                                    <select name="business_type" class="form-select form-select-lg form-select-solid @error('business_type') is-invalid @enderror">
                                        <option value="">{{ __('translation.select_business_type') }}</option>
                                        @foreach(\App\Enums\BusinessType::cases() as $type)
                                            <option value="{{ $type->value }}" {{ old('business_type', $merchant->business_type) == $type->value ? 'selected' : '' }}>
                                                {{ $type->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('business_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            @endif

                            @if($rejection->invalid_fields && in_array('address', $rejection->invalid_fields))
                            <div class="row mb-6">
                                <label class="col-lg-4 col-form-label required fw-bold fs-6">{{ __('translation.merchant_address') }}</label>
                                <div class="col-lg-8">
                                    <textarea name="address" class="form-control form-control-lg form-control-solid @error('address') is-invalid @enderror" 
                                              rows="3" placeholder="{{ __('translation.merchant_address') }}">{{ old('address', $merchant->address) }}</textarea>
                                    @error('address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            @endif
                            {{-- @dd($rejection->invalid_fields) --}}
                            @if($rejection->invalid_fields && in_array('trade_license_number', $rejection->invalid_fields))
                            <div class="row mb-6">
                                <label class="col-lg-4 col-form-label fw-bold fs-6">{{ __('translation.trade_license_number') }}</label>
                                <div class="col-lg-8">
                                    <input type="text" name="trade_license_number" class="form-control form-control-lg form-control-solid @error('trade_license_number') is-invalid @enderror" 
                                           placeholder="{{ __('translation.trade_license_number') }}" value="{{ old('trade_license_number', $merchant->trade_license_number) }}" />
                                    @error('trade_license_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            @endif

                            @if($rejection->invalid_fields && in_array('tax_certified_number', $rejection->invalid_fields))
                            <div class="row mb-6">
                                <label class="col-lg-4 col-form-label fw-bold fs-6">{{ __('translation.tax_number') }}</label>
                                <div class="col-lg-8">
                                    <input type="text" name="tax_certified_number" class="form-control form-control-lg form-control-solid @error('tax_certified_number') is-invalid @enderror" 
                                           placeholder="{{ __('translation.tax_number') }}" value="{{ old('tax_certified_number', $merchant->tax_certified_number) }}" />
                                    @error('tax_certified_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            @endif

                            @if($rejection->invalid_fields && in_array('country', $rejection->invalid_fields))
                            <div class="row mb-6">
                                <label class="col-lg-4 col-form-label fw-bold fs-6">{{ __('translation.country') }}</label>
                                <div class="col-lg-8">
                                    <select id="country_select" class="form-select form-select-lg form-select-solid @error('country') is-invalid @enderror" name="country" data-control="select2" data-placeholder="{{ __('translation.country') }}">
                                        @if(old('country', $merchant->country))
                                            <option value="{{ old('country', $merchant->country) }}" selected>{{ old('country', $merchant->country) }}</option>
                                        @endif
                                    </select>
                                    @error('country')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            @endif

                            @if($rejection->invalid_fields && in_array('city', $rejection->invalid_fields))
                            <div class="row mb-6">
                                <label class="col-lg-4 col-form-label fw-bold fs-6">{{ __('translation.city') }}</label>
                                <div class="col-lg-8">
                                    <select id="city_select" class="form-select form-select-lg form-select-solid @error('city') is-invalid @enderror" name="city" data-control="select2" data-placeholder="{{ __('translation.city') }}">
                                        @if(old('city', $merchant->city))
                                            <option value="{{ old('city', $merchant->city) }}" selected>{{ old('city', $merchant->city) }}</option>
                                        @endif
                                    </select>
                                    @error('city')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            @endif

                            <!-- Attachments Section - Only Missing Attachments -->
                            @if($rejection->invalid_fields && count($rejection->invalid_fields) > 0)
                            <div class="separator separator-dashed my-6"></div>
                            <div class="row mb-6">
                                <div class="col-lg-12">
                                    <h4 class="fw-bold mb-4">{{ __('translation.upload_missing_attachments') }}</h4>
                                </div>
                            </div>
                            {{-- @dd($rejection->missing_attachments) --}}
                            <div class="row mb-6">
                                @if(in_array('company_logo_document', $rejection->invalid_fields))
                                <div class="col-lg-6">
                                    <x:image-picker 
                                        value="{{ $logo ? asset($logo) : null }}"
                                        class="col-md-12"
                                        name="_company_logo"
                                        filed-name='company_logo'
                                        real-filed-id="company_logo"
                                    />
                                    <input type="file" name="company_logo" id="company_logo" class="d-none">
                                    @error('company_logo')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                @endif

                                {{-- @dd($rejection->invalid_fields) --}}
                                @if(in_array('tax_certificate_document', $rejection->invalid_fields))
                                <div class="col-lg-6">
                                    <x:image-picker 
                                        value="{{ $tax_certificate ? asset($tax_certificate) : null }}"
                                        class="col-md-12"
                                        name="_tax_certificate"
                                        filed-name='tax_certificate'
                                        real-filed-id="tax_certification"
                                    />
                                    <input type="file" name="tax_certification" id="tax_certification" class="d-none">
                                    @error('tax_certificate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                @endif

                                @if(in_array('trade_license_document', $rejection->invalid_fields))
                                <div class="col-lg-6">
                                    <x:image-picker 
                                        value="{{ $trade_license ? asset($trade_license) : null }}"
                                        class="col-md-12"
                                        name="_trade_license"
                                        filed-name='trade_license'
                                        real-filed-id="trade_license"
                                    />
                                    <input type="file" name="trade_license" id="trade_license" class="d-none">
                                    @error('trade_license')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                @endif

                                @if(in_array('identity_document', $rejection->invalid_fields))
                                <div class="col-lg-6">
                                    <x:image-picker 
                                        value="{{ $user_id ? asset($user_id) : null }}"
                                        class="col-md-12"
                                        name="_user_id"
                                        filed-name='user_id_document'
                                        real-filed-id="user_id"
                                    />
                                    <input type="file" name="user_id_document" id="user_id" class="d-none">
                                    @error('user_id_document')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                @endif
                            </div>
                            @endif

                            <div class="card-footer d-flex justify-content-end py-6 px-9">
                                <button type="reset" class="btn btn-light btn-active-light-primary me-2">{{ __('translation.discard') }}</button>
                                <button type="submit" class="btn btn-primary" id="kt_account_profile_details_submit">
                                    <span class="indicator-label">{{ __('translation.save_changes') }}</span>
                                    <span class="indicator-progress">
                                        {{ __('translation.please_wait') }}
                                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(function () {
        const countrySelect = $('#country_select');
        const citySelect = $('#city_select');

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
                placeholder: '{{ __('translation.country') }}',
                allowClear: true
            });
        }

        if (citySelect.length) {
            citySelect.select2({
                ajax: {
                    url: function() {
                        return '{{ route("city.select") }}';
                    },
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            search: params.term,
                            country_id: countrySelect.val()
                        };
                    },
                    processResults: function (data) {
                        const items = (data.data || data) || [];
                        return { results: items.map(c => ({ id: c.id, text: c.text })) };
                    },
                    cache: true
                },
                placeholder: '{{ __('translation.city') }}',
                allowClear: true
            });

            countrySelect.on('change', function() {
                citySelect.val(null).trigger('change');
            });
        }
    });
</script>
@endpush
