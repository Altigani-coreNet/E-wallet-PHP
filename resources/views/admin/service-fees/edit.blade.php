@extends('layouts.admin.admin_layout')

@section('title', 'Edit Service Fee')

@section('breadcrumb')
<li class="breadcrumb-item text-gray-600">
    <a href="{{ route('admin.service-fees.index') }}" class="text-gray-600">Service Fees Management</a>
</li>
<li class="breadcrumb-item text-gray-600">Edit Service Fee</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title">
            <h3>Edit Service Fee</h3>
        </div>
    </div>
    <div class="card-body py-4">
        <form action="{{ route('admin.service-fees.update', $serviceFee) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="row mb-6">
                <div class="col-lg-6">
                    <label class="col-form-label required fw-bold fs-6">Name</label>
                    <input type="text" name="name" class="form-control form-control-lg form-control-solid @error('name') is-invalid @enderror" 
                           value="{{ old('name', $serviceFee->name) }}" placeholder="Enter service fee name" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-lg-6">
                    <label class="col-form-label required fw-bold fs-6">Type</label>
                    <input type="text" name="type" class="form-control form-control-lg form-control-solid @error('type') is-invalid @enderror" 
                           value="{{ old('type', $serviceFee->type) }}" placeholder="Enter service fee type" required>
                    @error('type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="row mb-6">
                <div class="col-lg-6">
                    <label class="col-form-label required fw-bold fs-6">Fees</label>
                    <input type="number" name="fees" step="0.01" min="0" class="form-control form-control-lg form-control-solid @error('fees') is-invalid @enderror" 
                           value="{{ old('fees', $serviceFee->fees) }}" placeholder="Enter fee amount" required>
                    @error('fees')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="row">
                <div class="col-lg-12">
                    <div class="d-flex justify-content-end">
                        <a href="{{ route('admin.service-fees.index') }}" class="btn btn-light me-3">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <span class="indicator-label">Update Service Fee</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
