@extends('layouts.merchant.merchant_layout')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard - React')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Dashboard</li>
@endsection

@section('content')
{{-- React App Root Element (shared with all merchant pages) --}}
<div id="merchant-app-root" 
     data-merchant-id="{{ auth()->user()->merchant?->id ?? '' }}"
     data-api-token="{{ session('jwt_token') ?? '' }}">
    {{-- Loading placeholder while React initializes --}}
    <div class="d-flex align-items-center justify-content-center" style="min-height: 400px;">
        <div class="text-center">
            <span class="spinner-border spinner-border-lg text-primary" role="status" aria-hidden="true"></span>
            <p class="mt-3 text-muted fw-semibold">Loading dashboard...</p>
            <p class="text-muted small">Please wait while we fetch your data</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- Load React Merchant Application (includes dashboard routing) --}}
@vite(['resources/js/merchant-app.jsx'])

{{-- Optional: Set configuration via window object --}}
<script>
    // Alternative configuration method (in addition to data attributes)
    window.merchantAppConfig = {
        merchantId: {{ auth()->user()->merchant?->id ?? 'null' }},
        apiToken: "{{ session('jwt_token') ?? '' }}",
        apiBaseUrl: "{{ url('/api/v2/merchant') }}",
        baseUrl: "{{ url('/') }}",
        locale: "{{ app()->getLocale() }}"
    };
    
    // Debug: Log token availability (remove in production)
    console.log('Dashboard Configuration:', {
        hasMerchantId: !!window.merchantAppConfig.merchantId,
        hasToken: !!window.merchantAppConfig.apiToken,
        tokenLength: window.merchantAppConfig.apiToken?.length || 0,
        tokenPreview: window.merchantAppConfig.apiToken ? window.merchantAppConfig.apiToken.substring(0, 20) + '...' : 'NO TOKEN',
        apiBaseUrl: window.merchantAppConfig.apiBaseUrl,
        localStorageToken: !!localStorage.getItem('jwt_token'),
        sessionStorageToken: !!sessionStorage.getItem('jwt_token')
    });
    
    // If no token found, show warning
    if (!window.merchantAppConfig.apiToken && !localStorage.getItem('jwt_token') && !sessionStorage.getItem('jwt_token')) {
        console.warn('⚠️ JWT Token not found! Dashboard API calls will fail.');
        console.warn('Please ensure you are logged in and JWT token is stored in session/localStorage.');
    }
</script>
@endpush

@push('styles')
{{-- Add any custom styles for the React dashboard --}}
<style>
    /* Ensure the dashboard container takes full width */
    #merchant-app-root {
        width: 100%;
        min-height: 500px;
    }
    
    /* Loading state styles */
    #merchant-app-root .spinner-border-lg {
        width: 3rem;
        height: 3rem;
    }
    
    /* Print styles */
    @media print {
        /* Hide navigation and sidebar when printing */
        .app-sidebar,
        .app-header,
        .breadcrumb,
        #filters_button,
        #toolbar-print-btn,
        #export-dashboard-btn {
            display: none !important;
        }
        
        /* Expand content to full width */
        .app-container {
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        
        /* Optimize charts for print */
        .apexcharts-canvas {
            max-width: 100% !important;
        }
    }
</style>
@endpush

