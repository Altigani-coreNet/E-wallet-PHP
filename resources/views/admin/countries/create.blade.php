@extends('layouts.admin.admin_layout')

@section('title', 'Create Country')

@section('breadcrumb')
<li class="breadcrumb-item text-gray-600">
    <a href="{{ route('admin.countries.index') }}" class="text-gray-600">Countries Management</a>
</li>
<li class="breadcrumb-item text-gray-600">Create Country</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title">
            <h3>Create Country</h3>
        </div>
    </div>
    <div class="card-body py-4">
        <form action="{{ route('admin.countries.store') }}" method="POST">
            @csrf
            
            <div class="row mb-6">
                <div class="col-lg-6">
                    <label class="col-form-label required fw-bold fs-6">Name (English)</label>
                    <input type="text" name="name[en]" class="form-control form-control-lg form-control-solid @error('name.en') is-invalid @enderror" 
                           value="{{ old('name.en') }}" placeholder="Enter country name in English" required>
                    @error('name.en')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-lg-6">
                    <label class="col-form-label required fw-bold fs-6">Name (Arabic)</label>
                    <input type="text" name="name[ar]" class="form-control form-control-lg form-control-solid @error('name.ar') is-invalid @enderror" 
                           value="{{ old('name.ar') }}" placeholder="Enter country name in Arabic" required>
                    @error('name.ar')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="row mb-6">
                <div class="col-lg-6">
                    <label class="col-form-label required fw-bold fs-6">Short Name</label>
                    <input type="text" name="short_name" class="form-control form-control-lg form-control-solid @error('short_name') is-invalid @enderror" 
                           value="{{ old('short_name') }}" placeholder="Enter short name (e.g., US, UK)" required>
                    @error('short_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-lg-6">
                    <label class="col-form-label fw-bold fs-6">Code</label>
                    <input type="text" name="code" class="form-control form-control-lg form-control-solid @error('code') is-invalid @enderror" 
                           value="{{ old('code') }}" placeholder="Enter country code (e.g., US, UK)">
                    @error('code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="row mb-6">
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
                        <a href="{{ route('admin.countries.index') }}" class="btn btn-light me-3">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <span class="indicator-label">Create Country</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
