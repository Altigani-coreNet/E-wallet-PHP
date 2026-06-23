@extends('layouts.merchant.merchant_layout')

@section('title', 'Contract Agreement')

@section('content')
    <!-- Merchant React App Root -->
    <div id="merchant-app-root" data-api-token="{{ auth()->guard('external')->user()->getAccessToken() ?? '' }}"></div>

    <!-- Load Merchant React App -->
    @vite(['resources/js/merchant-app.jsx'])

    <!-- Translations for JS -->
    <script>
        window.translations = @json(__('translation'));
    </script>
    
    <!-- Load html2pdf library for PDF generation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
@endsection





