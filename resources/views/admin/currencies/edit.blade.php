@extends('layouts.admin.admin_layout')

@section('title', 'Edit Currency')

@section('breadcrumb')
<li class="breadcrumb-item text-gray-600">
    <a href="{{ route('admin.currencies.index') }}" class="text-gray-600">Currencies Management</a>
</li>
<li class="breadcrumb-item text-gray-600">Edit Currency</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title">
            <h3>Edit Currency</h3>
        </div>
    </div>
    <div class="card-body py-4">
        <form action="{{ route('admin.currencies.update', $currency) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="row mb-6">
                <div class="col-lg-6">
                    <label class="col-form-label required fw-bold fs-6">Country</label>
                    <input type="text" name="country" class="form-control form-control-lg form-control-solid @error('country') is-invalid @enderror" 
                           value="{{ old('country', $currency->country) }}" placeholder="Enter country name" required>
                    @error('country')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-lg-6">
                    <label class="col-form-label required fw-bold fs-6">Currency Name</label>
                    <input type="text" name="name" class="form-control form-control-lg form-control-solid @error('name') is-invalid @enderror" 
                           value="{{ old('name', $currency->name) }}" placeholder="Enter currency name" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="row mb-6">
                <div class="col-lg-6">
                    <label class="col-form-label required fw-bold fs-6">Symbol (English)</label>
                    <input type="text" name="symbol[en]" class="form-control form-control-lg form-control-solid @error('symbol.en') is-invalid @enderror" 
                           value="{{ old('symbol.en', $currency->getTranslation('symbol', 'en')) }}" placeholder="Enter currency symbol in English (e.g., $)" required>
                    @error('symbol.en')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-6">
                <div class="col-lg-6">
                    <label class="col-form-label required fw-bold fs-6">Symbol (Arabic)</label>
                    <input type="text" name="symbol[ar]" class="form-control form-control-lg form-control-solid @error('symbol.ar') is-invalid @enderror" 
                           value="{{ old('symbol.ar', $currency->getTranslation('symbol', 'ar')) }}" placeholder="Enter currency symbol in Arabic" required>
                    @error('symbol.ar')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-lg-6">
                    <label class="col-form-label required fw-bold fs-6">Currency Code (English)</label>
                    <input type="text" name="currency_code[en]" class="form-control form-control-lg form-control-solid @error('currency_code.en') is-invalid @enderror" 
                           value="{{ old('currency_code.en', $currency->getTranslation('currency_code', 'en')) }}" placeholder="Enter currency code in English (e.g., USD)" required>
                    @error('currency_code.en')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-6">
                <div class="col-lg-6">
                    <label class="col-form-label required fw-bold fs-6">Currency Code (Arabic)</label>
                    <input type="text" name="currency_code[ar]" class="form-control form-control-lg form-control-solid @error('currency_code.ar') is-invalid @enderror" 
                           value="{{ old('currency_code.ar', $currency->getTranslation('currency_code', 'ar')) }}" placeholder="Enter currency code in Arabic" required>
                    @error('currency_code.ar')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="row">
                <div class="col-lg-12">
                    <div class="d-flex justify-content-end">
                        <a href="{{ route('admin.currencies.index') }}" class="btn btn-light me-3">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <span class="indicator-label">Update Currency</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
