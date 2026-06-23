@extends('layouts.admin.admin_layout')

@section('title', 'View City')

@section('breadcrumb')
<li class="breadcrumb-item text-gray-600">
    <a href="{{ route('admin.cities.index') }}" class="text-gray-600">Cities Management</a>
</li>
<li class="breadcrumb-item text-gray-600">View City</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title">
            <h3>City Details</h3>
        </div>
        <div class="card-toolbar">
            <a href="{{ route('admin.cities.edit', $city) }}" class="btn btn-primary btn-sm">
                <i class="fas fa-edit"></i> Edit City
            </a>
        </div>
    </div>
    <div class="card-body py-4">
        <div class="row">
            <div class="col-lg-6">
                <div class="mb-6">
                    <label class="col-form-label fw-bold fs-6">Name (English)</label>
                    <div class="form-control form-control-lg form-control-solid">
                        {{ $city->getTranslation('name', 'en') }}
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="mb-6">
                    <label class="col-form-label fw-bold fs-6">Name (Arabic)</label>
                    <div class="form-control form-control-lg form-control-solid">
                        {{ $city->getTranslation('name', 'ar') }}
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-6">
                <div class="mb-6">
                    <label class="col-form-label fw-bold fs-6">Country</label>
                    <div class="form-control form-control-lg form-control-solid">
                        {{ $city->country ? $city->country->getTranslation('name', 'en') : 'N/A' }}
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="mb-6">
                    <label class="col-form-label fw-bold fs-6">Status</label>
                    <div class="form-control form-control-lg form-control-solid">
                        <span class="badge badge-{{ $city->status ? 'success' : 'danger' }}">
                            {{ $city->status ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-6">
                <div class="mb-6">
                    <label class="col-form-label fw-bold fs-6">Created At</label>
                    <div class="form-control form-control-lg form-control-solid">
                        {{ $city->created_at->format('M d, Y H:i:s') }}
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="mb-6">
                    <label class="col-form-label fw-bold fs-6">Updated At</label>
                    <div class="form-control form-control-lg form-control-solid">
                        {{ $city->updated_at->format('M d, Y H:i:s') }}
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-12">
                <div class="d-flex justify-content-end">
                    <a href="{{ route('admin.cities.index') }}" class="btn btn-light me-3">Back to List</a>
                    <a href="{{ route('admin.cities.edit', $city) }}" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit City
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
