@extends('layouts.admin.admin_layout')

@section('title', 'Currencies Management')

@section('breadcrumb')
<li class="breadcrumb-item text-gray-600">
    <a href="{{ route('admin.currencies.index') }}" class="text-gray-600">Currencies Management</a>
</li>
@endsection

@section('toolbar_actions')
<a href="{{ route('admin.currencies.create') }}" class="btn btn-primary btn-sm">
    <i class="fas fa-plus"></i> Add New Currency
</a>
@endsection

@section('content')
<div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title">
            <h3>Currencies Management</h3>
        </div>
    </div>
    <div class="card-body py-4">
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif
        
        <table class="table align-middle table-row-dashed fs-6 gy-5">
            <thead>
                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                    <th class="w-10px pe-2">
                        <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                            <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#currencies_table .form-check-input" value="1" />
                        </div>
                    </th>
                    <th class="min-w-125px">ID</th>
                    <th class="min-w-125px">Country</th>
                    <th class="min-w-125px">Name</th>
                    <th class="min-w-125px">Symbol (EN)</th>
                    <th class="min-w-125px">Symbol (AR)</th>
                    <th class="min-w-125px">Currency Code (EN)</th>
                    <th class="min-w-125px">Currency Code (AR)</th>
                    <th class="min-w-100px">Created At</th>
                    <th class="text-end min-w-100px">Actions</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 fw-semibold">
                @forelse($currencies as $currency)
                <tr>
                    <td>
                        <div class="form-check form-check-sm form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" value="{{ $currency->id }}" />
                        </div>
                    </td>
                    <td>{{ $currency->id }}</td>
                    <td>{{ $currency->country }}</td>
                    <td>{{ $currency->name }}</td>
                    <td>{{ $currency->getTranslation('symbol', 'en') }}</td>
                    <td>{{ $currency->getTranslation('symbol', 'ar') }}</td>
                    <td>{{ $currency->getTranslation('currency_code', 'en') }}</td>
                    <td>{{ $currency->getTranslation('currency_code', 'ar') }}</td>
                    <td>{{ $currency->created_at->format('M d, Y') }}</td>
                    <td class="text-end">
                        <a href="{{ route('admin.currencies.show', $currency) }}" class="btn btn-light btn-active-light-primary btn-sm" data-bs-toggle="tooltip" title="View">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('admin.currencies.edit', $currency) }}" class="btn btn-light btn-active-light-primary btn-sm" data-bs-toggle="tooltip" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('admin.currencies.destroy', $currency) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this currency?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-light btn-active-light-danger btn-sm" data-bs-toggle="tooltip" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center">No currencies found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
