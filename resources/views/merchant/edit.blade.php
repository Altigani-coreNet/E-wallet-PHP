@extends('layouts.merchant.merchant_layout')
@section('main-head', __('translation.edit_merchant'))
@section('page_title', __('translation.edit_merchant'))

@section('content')
<div class="post d-flex flex-column-fluid" id="kt_post">
    <!--begin::Container-->
    <div id="kt_content_container" class="container-xxl">
        <div class="row">
            <!-- Basic Information Card -->
            <div class="col-xl-12">
                <div class="card mb-5 mb-xl-10">
                    <div class="card-header">
                        <div class="card-title">
                            <h3 class="fw-bolder m-0">{{ __('translation.merchant_information') }}</h3>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('merchant.update') }}" method="POST" id="edit_merchant_form">
                            @csrf
                            @method('PUT')
                            
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

                            <div class="row mb-6">
                                <label class="col-lg-4 col-form-label required fw-bold fs-6">{{ __('translation.business_type') }}</label>
                                <div class="col-lg-8">
                                    {{-- @dd($merchant->business_type) --}}
                                    <select name="business_type" class="form-select form-select-lg form-select-solid @error('business_type') is-invalid @enderror">
                                        <option value="">{{ __('translation.select_business_type') }}</option>
                                        @foreach(\App\Enums\BusinessType::cases() as $type)
                                            <option value="{{ $type->value }}" {{ old('business_type', $merchant->business_type?->value) == $type->value ? 'selected' : '' }}>
                                                {{ $type->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('business_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

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

                            <div class="row mb-6">
                                <label class="col-lg-4 col-form-label fw-bold fs-6">{{ __('translation.tax_number') }}</label>
                                <div class="col-lg-8">
                                    <input type="text" name="tax_certified_number" class="form-control form-control-lg form-control-solid @error('tax_certified_number') is-invalid @enderror" 
                                           placeholder="{{ __('translation.tax_number') }}" value="{{ old('tax_number', $merchant->tax_number) }}" />
                                    @error('tax_certified_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-6">
                                <label class="col-lg-4 col-form-label fw-bold fs-6">{{ __('translation.country') }}</label>
                                <div class="col-lg-8">
                                    <select id="country_select" class="form-select form-select-lg form-select-solid @error('country_id') is-invalid @enderror" name="country_id" data-control="select2" data-placeholder="{{ __('translation.country') }}">
                                        @if(old('country_id', $merchant->country_id))
                                            <option value="{{ old('country_id', $merchant->country_id) }}" selected>{{ $merchant->country?->name }}</option>
                                        @endif
                                    </select>
                                    @error('country_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-6">
                                <label class="col-lg-4 col-form-label fw-bold fs-6">{{ __('translation.city') }}</label>
                                <div class="col-lg-8">
                                    <select id="city_select" class="form-select form-select-lg form-select-solid @error('city_id') is-invalid @enderror" name="city_id" data-control="select2" data-placeholder="{{ __('translation.city') }}">
                                        @if(old('city_id', $merchant->city_id))
                                            <option value="{{ old('city_id', $merchant->city_id) }}" selected>{{  $merchant->city?->name }}</option>
                                        @endif
                                    </select>
                                    @error('city')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-6">
                                <label class="col-lg-4 col-form-label fw-bold fs-6">{{ __('translation.merchant_code') }}</label>
                                <div class="col-lg-8">
                                    <input type="text" class="form-control form-control-lg form-control-solid" 
                                           value="{{ $merchant->merchant_code }}" readonly disabled />
                                    <div class="form-text">{{ __('translation.this_code_is_automatically_generated') }}</div>
                                </div>
                            </div>

                            <div class="card-footer d-flex justify-content-end py-6 px-9">
                                <button type="reset" class="btn btn-light btn-active-light-primary me-2">{{ __('translation.discard') }}</button>
                                <button type="submit" class="btn btn-primary" id="kt_account_profile_details_submit">
                                    <span class="indicator-label">
                                        @if($merchant->status === 'rejected')
                                            {{ __('translation.save_changes') }}
                                        @else
                                            {{ __('translation.request_update') }}
                                        @endif
                                    </span>
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

            <!-- Attachments Card -->
        <div class="col-xl-12">
                <div class="card mb-5 mb-xl-10">
                    <div class="card-header">
                        <div class="card-title">
                            <h3 class="fw-bolder m-0">{{ __('translation.merchant_attachments') }}</h3>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('merchant.update.attachments') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            
                            <div class="row mb-6">
                                <div class="col-lg-6">
                                    <x:image-picker 
                                        value="{{ $logo ? asset($logo) : null }}"
                                        class="col-md-12"
                                        name="_company_logo"
                                        filed-name='company_logo'
                                        real-filed-id="company_logo"
                                    />
                                    <input type="file" name="company_logo" id="company_logo" class="d-none">
                                    @error('logo')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                           <div class="col-lg-6">
                                    <x:image-picker 
                                        value="{{ $tax_certificate ? asset($tax_certificate) : null }}"
                                        class="col-md-12"
                                        name="_tax_certificate"
                                        filed-name='image2'
                                        real-filed-id="tax_certification"
                                    />
                                    <input type="file" name="tax_certificate" id="tax_certification" class="d-none">
                                    @error('tax_certificate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                           <div class="col-lg-6">
                                    <x:image-picker 
                                        value="{{ $trade_license ? asset($trade_license) : null }}"
                                        class="col-md-12"
                                        name="_trade_license"
                                        filed-name='image2'
                                        real-filed-id="trade_license"
                                    />
                                    <input type="file" name="trade_license" id="trade_license" class="d-none">
                                    @error('trade_license')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                           <div class="col-lg-6">
                                    <x:image-picker 
                                        value="{{ $user_id ? asset($user_id) : null }}"
                                        class="col-md-12"
                                        name="_user_id"
                                        filed-name='image2'
                                        real-filed-id="user_id"
                                    />
                                    <input type="file" name="user_id_document" id="user_id" class="d-none">
                                    @error('user_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="card-footer d-flex justify-content-end py-6 px-9">
                                <button type="reset" class="btn btn-light btn-active-light-primary me-2">{{ __('translation.discard') }}</button>
                                <button type="submit" class="btn btn-primary">
                                    <span class="indicator-label">
                                        @if($merchant->status === 'rejected')
                                            {{ __('translation.save_changes') }}
                                        @else
                                            {{ __('translation.request_update') }}
                                        @endif
                                    </span>
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
                    url: '{{ route("countries.select") }}',
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