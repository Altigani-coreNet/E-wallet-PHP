@extends('layouts.merchant.merchant_layout')

@section('title', 'Merchant - Batches')

@section('content')
<!-- React App Root -->
<div id="merchant-app-root" 
     data-api-token="{{ session('jwt_token') ?? auth()->user()->getAccessToken() ?? '' }}"
     data-merchant-id="{{ auth()->user()->merchant_id ?? '' }}">
</div>
@endsection

@push('scripts')
<script>
    // Set merchant configuration for the React app
    window.merchantAppConfig = window.merchantAppConfig || {};
    window.merchantAppConfig.merchantId = {{ auth()->user()->merchant_id ?? 'null' }};
    window.merchantAppConfig.apiToken = '{{ session('jwt_token') ?? auth()->user()->getAccessToken() ?? '' }}';
    window.merchantAppConfig.apiBaseUrl = '{{ url('/api/v1/merchant') }}';
</script>
@viteReactRefresh
@vite(['resources/js/merchant-app.jsx'])
@endpush
