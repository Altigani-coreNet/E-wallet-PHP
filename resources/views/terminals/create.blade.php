@extends('layouts.admin.admin_layout')

@section('title', __('translation.add_terminal'))

@section('breadcrumbs')
    <!--begin::Breadcrumb-->
    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">
            <a href="{{ route('terminals.index') }}" class="text-muted text-hover-primary">{{ __('translation.terminals_management') }}</a>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">{{ __('translation.add_terminal') }}</li>
        <!--end::Item-->
    </ul>
    <!--end::Breadcrumb-->
@endsection
@section('toolbar_actions')
<div class="d-flex align-items-center gap-2 gap-lg-3">
    <!--begin::Filter menu-->
    <div class="m-0">
        <!--begin::Menu toggle-->
        {{-- <button id="filters_button" class="btn btn-sm btn-flex btn-secondary fw-bold" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
        <i class="ki-duotone ki-filter fs-6 text-muted me-1">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>{{ __('translation.filter') }}</button> --}}
        <!--end::Menu toggle-->
        <!--begin::Menu 1-->
       
        <!--end::Menu 1-->
    </div>
    <!--end::Filter menu-->
    <!--begin::Secondary button-->
    <!--end::Secondary button-->    
    <!--begin::Primary button-->
    <a href='{{ route('terminals.index')}}' class="btn btn-sm fw-bold btn-light-danger">
        <i class="ki-duotone ki-arrow-left fs-3">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>

        {{ __('translation.back_to_list') }}</a>
    <!--end::Primary button-->
</div>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ __('translation.add_new_terminal') }}</h3>
        {{-- <div class="card-toolbar">
            <a href="{{ route('terminals.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> {{ __('translation.back_to_list') }}
            </a>
        </div> --}}
    </div>
        <form action="{{ route('terminals.store') }}" method="POST">
            @csrf
            
           <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="name" class="form-label">{{ __('translation.terminal_name') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="terminal_id" class="form-label">{{ __('translation.terminal_id') }}</label>
                        <input type="text" class="form-control @error('terminal_id') is-invalid @enderror" 
                               id="terminal_id" name="terminal_id" value="{{ old('terminal_id') }}" 
                               placeholder="{{ __('translation.terminal_id_auto_generated') }}">
                        <small class="form-text text-muted">{{ __('translation.terminal_id_auto_generated') }}</small>
                        @error('terminal_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="brand" class="form-label">{{ __('translation.brand_name') }}</label>
                        <input type="text" class="form-control @error('brand') is-invalid @enderror" 
                               id="brand" name="brand" value="{{ old('brand') }}" 
                               placeholder="e.g., Verifone, Ingenico">
                        @error('brand')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="model" class="form-label">{{ __('translation.model') }}</label>
                        <input type="text" class="form-control @error('model') is-invalid @enderror" 
                               id="model" name="model" value="{{ old('model') }}">
                        @error('model')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="manufacturer" class="form-label">{{ __('translation.manufacturer') }}</label>
                        <input type="text" class="form-control @error('manufacturer') is-invalid @enderror" 
                               id="manufacturer" name="manufacturer" value="{{ old('manufacturer') }}">
                        @error('manufacturer')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="serial_no" class="form-label">{{ __('translation.serial_no') }}</label>
                        <input type="text" class="form-control @error('serial_no') is-invalid @enderror" 
                               id="serial_no" name="serial_no" value="{{ old('serial_no') }}">
                        @error('serial_no')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="sdk_id" class="form-label">{{ __('translation.sdk_id') }}</label>
                        <input type="text" class="form-control @error('sdk_id') is-invalid @enderror" 
                               id="sdk_id" name="sdk_id" value="{{ old('sdk_id') }}">
                        @error('sdk_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="sdk_version" class="form-label">{{ __('translation.sdk_version') }}</label>
                        <input type="text" class="form-control @error('sdk_version') is-invalid @enderror" 
                               id="sdk_version" name="sdk_version" value="{{ old('sdk_version') }}">
                        @error('sdk_version')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="android_os" class="form-label">{{ __('translation.android_os') }}</label>
                        <input type="text" class="form-control @error('android_os') is-invalid @enderror" 
                               id="android_os" name="android_os" value="{{ old('android_os') }}" 
                               placeholder="e.g., Android 11, Android 12">
                        @error('android_os')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
{{--                 
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="add_type" class="form-label">{{ __('translation.add_type') }}</label>
                        <select class="form-control @error('add_type') is-invalid @enderror" 
                                id="add_type" name="add_type">
                            <option value="static" {{ old('add_type', 'static') == 'static' ? 'selected' : '' }}>
                                {{ __('translation.static') }} ({{ __('translation.manual_addition') }})
                            </option>
                            <option value="auto" {{ old('add_type') == 'auto' ? 'selected' : '' }}>
                                {{ __('translation.auto') }} ({{ __('translation.api_registration') }})
                            </option>
                        </select>
                        @error('add_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div> --}}
            </div>
            
            {{-- <div class="row">
                <div class="col-md-6">
                    <x-select-options 
                        class="col-md-12" 
                        name="status" 
                        filed-name="is_active"
                        :options="['active', 'inactive']"
                        :value="old('is_active', 'active')"
                    />
                    @error('is_active')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div> --}}
           </div>
            <div class="card-footer d-flex justify-content-end">
                <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> {{ __('translation.save') }}
                </button>
                <a href="{{ route('terminals.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> {{ __('translation.cancel') }}
                </a>
            </div>
            </div>
            
        </form>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Form initialization code can be added here if needed
});
</script>
@endpush 