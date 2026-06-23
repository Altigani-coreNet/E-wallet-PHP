@extends('layouts.admin.admin_layout')

@section('main-head', __('translation.edit_unit'))
@section('page_title', 'Edit Unit')
@section('breadcrumbs')
    <!--begin::Breadcrumb-->
    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">
            <a href="index.html" class="text-muted text-hover-primary">Home</a>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">Units</li>
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <li class="breadcrumb-item text-muted">Edit Unit</li>
        <!--end::Item-->
    </ul>
    <!--end::Breadcrumb-->
@endsection

@section('content')
    <div class="post d-flex flex-column-fluid" id="kt_post">
        <div id="kt_content_container" class="container-xxl">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{__('translation.edit_unit')}}</h3>
                    <div class="card-toolbar">
                        <a href="{{ route('units.index') }}" class="btn btn-sm btn-light-danger">
                            <i class="fas fa-arrow-left"></i> {{__('translation.back')}}
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('units.update', $unit) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <x:text-input class="col-md-6" name="unit_name_in_arabic" filedname='name[ar]' value="{{ old('name[ar]', $unit->getTranslation('name', 'ar')) }}" />
                            <x:text-input class="col-md-6" name="unit_name_in_english" filedname='name[en]' value="{{ old('name[en]', $unit->getTranslation('name', 'en')) }}" />
                            
                            <x:text-input class="col-md-6" name="code" filedname='code' value="{{ old('code', $unit->code) }}" />
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="base_unit_id">{{ __('translation.base_unit') }}</label>
                                    <select name="base_unit_id" class="form-control @error('base_unit_id') is-invalid @enderror">
                                        <option value="">{{ __('translation.select_base_unit') }}</option>
                                        @foreach($units as $baseUnit)
                                            <option value="{{ $baseUnit->id }}" {{ old('base_unit_id', $unit->base_unit_id) == $baseUnit->id ? 'selected' : '' }}>
                                                {{ $baseUnit->getTranslation('name', app()->getLocale()) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('base_unit_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="operator">{{ __('translation.operator') }}</label>
                                    <select name="operator" class="form-control @error('operator') is-invalid @enderror">
                                        <option value="">{{ __('translation.select_operator') }}</option>
                                        <option value="*" {{ old('operator', $unit->operator) == '*' ? 'selected' : '' }}>*</option>
                                        <option value="/" {{ old('operator', $unit->operator) == '/' ? 'selected' : '' }}>/</option>
                                        <option value="+" {{ old('operator', $unit->operator) == '+' ? 'selected' : '' }}>+</option>
                                        <option value="-" {{ old('operator', $unit->operator) == '-' ? 'selected' : '' }}>-</option>
                                    </select>
                                    @error('operator')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <x:text-input class="col-md-6" name="operation_value" filedname='operation_value' type="number" step="0.01" value="{{ old('operation_value', $unit->operation_value) }}" />
                            
                            <x:status-filed name="unit_status" filed-name='status' class="col-md-6" value="{{ $unit->status }}" />

                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="col-12 mt-5">
                                <button type="submit" class="btn btn-primary">
                                    {{__('translation.Save')}}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('admin_assets/js/custom/index.js')}}"></script>
@endpush 