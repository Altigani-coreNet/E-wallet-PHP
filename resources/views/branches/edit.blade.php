@extends('layouts.admin.admin_layout')

@section('title', 'Edit Branch')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Edit Branch: {{ $branch->name }}</h3>
        <div class="card-toolbar">
            <a href="{{ route('branches.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Branches
            </a>
        </div>
    </div>
    <div class="card-body">
        <form action="{{ route('branches.update', $branch->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="name" class="form-label">Branch Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name', $branch->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="col-md-6">
                    <x-select2-input 
                        class="col-md-12" 
                        name="merchant" 
                        filed-name="merchant_id"
                        url="{{ route('merchants.select') }}"
                        :name-value="old('merchant_id', $branch->merchant_id) ? \App\Models\Merchant::find(old('merchant_id', $branch->merchant_id))->name : null"
                        :value="old('merchant_id', $branch->merchant_id)"
                    />
                    @error('merchant_id')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="form-group mb-3">
                <label for="address" class="form-label">Address</label>
                <textarea class="form-control @error('address') is-invalid @enderror" 
                          id="address" name="address" rows="3">{{ old('address', $branch->address) }}</textarea>
                @error('address')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <x-select-options 
                        class="col-md-12" 
                        name="status" 
                        filed-name="is_active"
                        :options="['active', 'inactive']"
                        :value="old('is_active', $branch->is_active ? 'active' : 'inactive')"
                    />
                    @error('is_active')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Branch
                </button>
                <a href="{{ route('branches.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection 