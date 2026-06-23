@extends('layouts.merchant.merchant_layout')

@section('title', 'Sales')

@section('page_title', 'Sales Management')

@section('content')
<div id="sales-app-root" class="w-full h-full" data-api-token="{{ $api_token ?? '' }}"></div>
@endsection

@push('scripts')
    @vite(['resources/js/sales-app.jsx'])
@endpush

