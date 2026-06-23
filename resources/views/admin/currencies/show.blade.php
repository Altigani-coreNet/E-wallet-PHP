@extends('layouts.admin.admin_layout')

@section('title', 'Currency Details')

@section('breadcrumb')
<li class="breadcrumb-item text-gray-600">
    <a href="{{ route('admin.currencies.index') }}" class="text-gray-600">Currencies Management</a>
</li>
<li class="breadcrumb-item text-gray-600">Currency Details</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title">
            <h3>Currency Details</h3>
        </div>
        <div class="card-toolbar">
            <a href="{{ route('admin.currencies.edit', $currency) }}" class="btn btn-primary btn-sm">
                <i class="fas fa-edit"></i> Edit Currency
            </a>
        </div>
    </div>
    <div class="card-body py-4">
        <div class="row">
            <div class="col-lg-6">
                <div class="mb-6">
                    <label class="col-form-label fw-bold fs-6">ID</label>
                    <div class="fw-semibold fs-6 text-gray-800">{{ $currency->id }}</div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="mb-6">
                    <label class="col-form-label fw-bold fs-6">Country</label>
                    <div class="fw-semibold fs-6 text-gray-800">{{ $currency->country }}</div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="mb-6">
                    <label class="col-form-label fw-bold fs-6">Currency Name</label>
                    <div class="fw-semibold fs-6 text-gray-800">{{ $currency->name }}</div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="mb-6">
                    <label class="col-form-label fw-bold fs-6">Symbol (English)</label>
                    <div class="fw-semibold fs-6 text-gray-800">{{ $currency->getTranslation('symbol', 'en') }}</div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="mb-6">
                    <label class="col-form-label fw-bold fs-6">Symbol (Arabic)</label>
                    <div class="fw-semibold fs-6 text-gray-800">{{ $currency->getTranslation('symbol', 'ar') }}</div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="mb-6">
                    <label class="col-form-label fw-bold fs-6">Currency Code (English)</label>
                    <div class="fw-semibold fs-6 text-gray-800">{{ $currency->getTranslation('currency_code', 'en') }}</div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="mb-6">
                    <label class="col-form-label fw-bold fs-6">Currency Code (Arabic)</label>
                    <div class="fw-semibold fs-6 text-gray-800">{{ $currency->getTranslation('currency_code', 'ar') }}</div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="mb-6">
                    <label class="col-form-label fw-bold fs-6">Created At</label>
                    <div class="fw-semibold fs-6 text-gray-800">{{ $currency->created_at->format('M d, Y H:i:s') }}</div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="mb-6">
                    <label class="col-form-label fw-bold fs-6">Updated At</label>
                    <div class="fw-semibold fs-6 text-gray-800">{{ $currency->updated_at->format('M d, Y H:i:s') }}</div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-12">
                <div class="d-flex justify-content-end">
                    <a href="{{ route('admin.currencies.index') }}" class="btn btn-light me-3">Back to List</a>
                    <a href="{{ route('admin.currencies.edit', $currency) }}" class="btn btn-primary">Edit Currency</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
