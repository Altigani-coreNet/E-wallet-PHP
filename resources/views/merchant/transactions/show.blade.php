@extends('layouts.merchant.merchant_layout')

@section('title', 'Merchant - Transaction Details')

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
    window.merchantTransactionsConfig = {
        merchantId: {{ auth()->user()->merchant_id ?? 'null' }},
        transactionId: {{ $transaction->id ?? 'null' }},
        apiBaseUrl: '{{ url('/api/v1/merchant') }}',
        csrfToken: '{{ csrf_token() }}',
        apiToken: '{{ session('jwt_token') ?? auth()->user()->getAccessToken() ?? '' }}'
    };
    
    // Also set in merchantAppConfig for consistency
    window.merchantAppConfig = window.merchantAppConfig || {};
    window.merchantAppConfig.merchantId = {{ auth()->user()->merchant_id ?? 'null' }};
    window.merchantAppConfig.apiToken = '{{ session('jwt_token') ?? auth()->user()->getAccessToken() ?? '' }}';
</script>
@viteReactRefresh
@vite(['resources/js/merchant-app.jsx'])
@endpush

{{-- 
    LEGACY BLADE VERSION BELOW - KEPT FOR REFERENCE
    Remove this entire commented section once React version is confirmed working
--}}
