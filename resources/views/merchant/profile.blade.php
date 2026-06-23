@extends('layouts.merchant.merchant_layout')
@section('main-head', __('translation.my_profile'))
@section('page_title', __('translation.my_profile'))
{{-- @dd(auth()->user()->merchant) --}}
@section('content')
    <!-- Merchant React App Root -->
    <div id="merchant-app-root" data-api-token="{{ auth()->guard('external')->user()->getAccessToken() ?? '' }}"></div>

    <!-- Load Merchant React App -->
    @vite(['resources/js/merchant-app.jsx'])

    <!-- Translations for JS -->
    <script>
        window.translations = @json(__('translation'));
    </script>
@endsection
