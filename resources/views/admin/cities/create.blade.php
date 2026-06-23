@extends('layouts.admin.admin_layout')

@section('title', 'Create City')

@section('breadcrumb')
<li class="breadcrumb-item text-gray-600">
    <a href="{{ route('admin.cities.index') }}" class="text-gray-600">Cities Management</a>
</li>
<li class="breadcrumb-item text-gray-600">Create City</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title">
            <h3>Create City</h3>
        </div>
    </div>
    <div class="card-body py-4">
        <form action="{{ route('admin.cities.store') }}" method="POST">
            @csrf
            
            <div class="row mb-6">
                <div class="col-lg-6">
                    <label class="col-form-label required fw-bold fs-6">Name (English)</label>
                    <input type="text" name="name[en]" class="form-control form-control-lg form-control-solid @error('name.en') is-invalid @enderror" 
                           value="{{ old('name.en') }}" placeholder="Enter city name in English" required>
                    @error('name.en')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-lg-6">
                    <label class="col-form-label required fw-bold fs-6">Name (Arabic)</label>
                    <input type="text" name="name[ar]" class="form-control form-control-lg form-control-solid @error('name.ar') is-invalid @enderror" 
                           value="{{ old('name.ar') }}" placeholder="Enter city name in Arabic" required>
                    @error('name.ar')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="row mb-6">
                <div class="col-lg-6">
                    <label class="col-form-label required fw-bold fs-6">Country</label>
                    <select name="country_id" class="form-control form-control-lg form-control-solid @error('country_id') is-invalid @enderror" required>
                        <option value="">Select Country</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->id }}" {{ old('country_id') == $country->id ? 'selected' : '' }}>
                                {{ $country->getTranslation('name', 'en') }}
                            </option>
                        @endforeach
                    </select>
                    @error('country_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-lg-6">
                    <label class="col-form-label required fw-bold fs-6">Status</label>
                    <select name="status" class="form-control form-control-lg form-control-solid @error('status') is-invalid @enderror" required>
                        <option value="">Select Status</option>
                        <option value="1" {{ old('status') === '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ old('status') === '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="row">
                <div class="col-lg-12">
                    <div class="d-flex justify-content-end">
                        <a href="{{ route('admin.cities.index') }}" class="btn btn-light me-3">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <span class="indicator-label">Create City</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
