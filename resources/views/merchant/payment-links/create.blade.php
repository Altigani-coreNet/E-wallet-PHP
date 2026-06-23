@extends('layouts.merchant.merchant_layout')

@section('title', 'Create Payment Link')

@section('content')
    <!-- Merchant React App Root -->
    <div id="merchant-app-root" data-api-token="{{ auth()->user()->getAccessToken() ?? '' }}"></div>

    <!-- Load Merchant React App -->
    @vite(['resources/js/merchant-app.jsx'])

    <!-- Translations for JS -->
    <script>
        window.translations = @json(__('translation'));
    </script>
@endsection
