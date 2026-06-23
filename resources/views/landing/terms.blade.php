@extends('layouts.landing')

@section('title', __('Contract Terms & Conditions'))
@section('meta_description', __('Contract terms and conditions for using Corenet Tech payment and POS solutions.'))

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class=" ">
                <div class="card-body">
                    <h1 class="mb-4">{{ __('Contract Terms & Conditions') }}</h1>
                    <p class="text-muted mb-5">{{ __('Last updated') }}: {{ date('F d, Y') }}</p>

                    @if($terms)
                        <div class="terms-content">
                            {!! $terms !!}
                        </div>
                    @else
                        <div class="alert alert-info">
                            {{ __('Contract terms are not available in') }} {{ $locale === 'en' ? 'English' : 'العربية' }}.
                            @if($locale === 'ar')
                                <a href="{{ route('terms', ['locale' => 'en']) }}" class="alert-link">View in English</a>
                            @else
                                <a href="{{ route('terms', ['locale' => 'ar']) }}" class="alert-link">عرض باللغة العربية</a>
                            @endif
                        </div>
                    @endif

                    {{-- <div class="mt-5">
                        <h2 class="h4 mb-4">{{ __('Additional Information') }}</h2>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="card h-100 border-0 bg-light">
                                    <div class="card-body">
                                        <h3 class="h5 mb-3">{{ __('Transaction Fee') }}</h3>
                                        <p class="mb-0">{{ \App\Models\Setting::getTransactionFee() }}%</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card h-100 border-0 bg-light">
                                    <div class="card-body">
                                        <h3 class="h5 mb-3">{{ __('Settlement Period') }}</h3>
                                        <p class="mb-0">{{ \App\Models\Setting::getSettlementPeriod() }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card h-100 border-0 bg-light">
                                    <div class="card-body">
                                        <h3 class="h5 mb-3">{{ __('Contract Duration') }}</h3>
                                        <p class="mb-0">{{ \App\Models\Setting::getContractDuration() }} {{ __('months') }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card h-100 border-0 bg-light">
                                    <div class="card-body">
                                        <h3 class="h5 mb-3">{{ __('Supported Payment Methods') }}</h3>
                                        <p class="mb-0">{{ implode(', ', \App\Models\Setting::getPaymentMethods()) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div> --}}

                    <div class="mt-5 text-center">
                        <p class="mb-4">{{ __('Have questions about our terms?') }}</p>
                        <a href="{{ route('merchant.register') }}" class="btn btn-primary me-3">{{ __('Register Now') }}</a>
                        <a href="#" class="btn btn-outline-primary">{{ __('Contact Support') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($locale === 'ar')
    @section('additional_css')
    <style>
        body {
            direction: rtl;
            text-align: right;
        }
        .terms-content ul, 
        .terms-content ol {
            padding-right: 1.5rem;
            padding-left: 0;
        }
    </style>
    @endsection
@endif
@endsection