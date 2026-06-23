@extends('layouts.admin.admin_layout')

@section('title', 'Service Fee Details')

@section('breadcrumb')
<li class="breadcrumb-item text-gray-600">
    <a href="{{ route('admin.service-fees.index') }}" class="text-gray-600">Service Fees Management</a>
</li>
<li class="breadcrumb-item text-gray-600">Service Fee Details</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title">
            <h3>Service Fee Details</h3>
        </div>
        <div class="card-toolbar">
            <a href="{{ route('admin.service-fees.edit', $serviceFee) }}" class="btn btn-primary btn-sm">
                <i class="fas fa-edit"></i> Edit Service Fee
            </a>
        </div>
    </div>
    <div class="card-body py-4">
        <div class="row">
            <div class="col-lg-6">
                <div class="mb-6">
                    <label class="col-form-label fw-bold fs-6">ID</label>
                    <div class="fw-semibold fs-6 text-gray-800">{{ $serviceFee->id }}</div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="mb-6">
                    <label class="col-form-label fw-bold fs-6">Name</label>
                    <div class="fw-semibold fs-6 text-gray-800">{{ $serviceFee->name }}</div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-6">
                <div class="mb-6">
                    <label class="col-form-label fw-bold fs-6">Type</label>
                    <div class="fw-semibold fs-6 text-gray-800">
                        <span class="badge badge-light-info">{{ ucfirst($serviceFee->type) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="mb-6">
                    <label class="col-form-label fw-bold fs-6">Fees</label>
                    <div class="fw-semibold fs-6 text-gray-800">{{ number_format($serviceFee->fees, 2) }}</div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-6">
                <div class="mb-6">
                    <label class="col-form-label fw-bold fs-6">Created At</label>
                    <div class="fw-semibold fs-6 text-gray-800">{{ $serviceFee->created_at->format('M d, Y H:i:s') }}</div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="mb-6">
                    <label class="col-form-label fw-bold fs-6">Updated At</label>
                    <div class="fw-semibold fs-6 text-gray-800">{{ $serviceFee->updated_at->format('M d, Y H:i:s') }}</div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-12">
                <div class="d-flex justify-content-end">
                    <a href="{{ route('admin.service-fees.index') }}" class="btn btn-light me-3">Back to List</a>
                    <a href="{{ route('admin.service-fees.edit', $serviceFee) }}" class="btn btn-primary">
                        <span class="indicator-label">Edit Service Fee</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
