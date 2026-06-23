@extends('layouts.admin.admin_layout')

@section('title', 'Create Branch')

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
        <li class="breadcrumb-item text-muted">{{ __('translation.merchant_management') }}</li>
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <li class="breadcrumb-item text-muted">{{ __('translation.branches') }}</li>
        <!--end::Item-->
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <li class="breadcrumb-item text-muted">{{ __('translation.add_branch') }}</li>
    </ul>
    <!--end::Breadcrumb-->
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Create New Branch</h3>
            <div class="card-toolbar">
                <a href="{{ route('branches.index') }}" class="btn btn-sm btn-danger">
                    <i class="fas fa-arrow-left"></i> Back to Branches
                </a>
            </div>
        </div>
        <form action="{{ route('branches.store') }}" method="POST">

            <div class="card-body">
                @csrf
                <input type="hidden" name="merchant_id" value="{{ old('merchant_id', auth()->user()->merchant_id) }}">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="name" class="form-label">Branch Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <x-select2-input class="col-md-12" name="merchant" filed-name="merchant_id"
                            url="{{ route('merchants.select') }}" :name-value="old('merchant_id') ? \App\Models\Merchant::find(old('merchant_id'))->name : null" :value="old('merchant_id')" />
                        @error('merchant_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                

                <div class="form-group mb-3">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="3">{{ old('address') }}</textarea>
                    @error('address')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <x-select-options class="col-md-12" name="status" filed-name="is_active" :options="['active', 'inactive']"
                            :value="old('is_active', 'active')" />
                        @error('is_active')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class=" card-footer p-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Create Branch
                    </button>
                    <a href="{{ route('branches.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>

        </form>
    </div>
    </div>
@endsection
