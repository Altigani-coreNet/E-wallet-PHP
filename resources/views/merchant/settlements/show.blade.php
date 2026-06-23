@extends('layouts.merchant.merchant_layout')

@section('title', __('translation.settlement_details'))

@section('breadcrumbs')
    <!--begin::Breadcrumb-->
    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">
            <a href="{{ route('merchant.dashboard') }}" class="text-muted text-hover-primary">{{ __('translation.home') }}</a>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">
            <a href="{{ route('merchant.settlements.index') }}" class="text-muted text-hover-primary">{{ __('translation.settlements') }}</a>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">{{ $settlement->settlement_number }}</li>
        <!--end::Item-->
    </ul>
    <!--end::Breadcrumb-->
@endsection

@section('toolbar_actions')
<div class="d-flex align-items-center gap-2 gap-lg-3">
    <!--begin::Back button-->
    <a href="{{ route('merchant.settlements.index') }}" class="btn btn-sm btn-flex btn-secondary fw-bold">
        <i class="ki-duotone ki-arrow-left fs-6 text-muted me-1">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
        {{ __('translation.back_to_settlements') }}
    </a>
    <!--end::Back button-->
</div>
@endsection

@section('content')
<div class="post d-flex flex-column-fluid" id="kt_post">
    <!--begin::Container-->
    <div id="kt_content_container" class="container-xxl">
        <!-- First Row -->
        <div class="row g-5 g-xl-8 mb-5">
            <!-- Settlement Details -->
            <div class="col-md-6">
                <div class="card card-flush h-100">
                    <div class="card-header pt-5">
                        <div class="card-title d-flex flex-column">
                            <div class="d-flex align-items-center">
                                <span class="fs-2hx fw-bold text-dark me-2 lh-1 ls-n2">{{ $settlement->settlement_number }}</span>
                            </div>
                            <span class="text-gray-400 pt-1 fw-semibold fs-6">{{ __('translation.settlement_number') }}</span>
                        </div>
                    </div>
                    <div class="card-body pt-2 pb-4 d-flex align-items-center flex-wrap">
                        <div class="d-flex flex-column flex-grow-1 pe-8">
                            <div class="d-flex align-items-center">
                                <span class="fs-6 fw-semibold text-gray-400 d-block align-self-start me-2">{{ __('translation.status') }}:</span>
                                @switch($settlement->status)
                                    @case('pending')
                                        <span class="badge badge-light-warning fs-7 fw-bold">{{ __('translation.pending') }}</span>
                                        @break
                                    @case('settled')
                                        <span class="badge badge-light-success fs-7 fw-bold">{{ __('translation.settled') }}</span>
                                        @break
                                    @case('failed')
                                        <span class="badge badge-light-danger fs-7 fw-bold">{{ __('translation.failed') }}</span>
                                        @break
                                    @default
                                        <span class="badge badge-light-secondary fs-7 fw-bold">{{ ucfirst($settlement->status) }}</span>
                                @endswitch
                            </div>
                            <div class="d-flex align-items-center mt-2">
                                <span class="fs-6 fw-semibold text-gray-400 d-block align-self-start me-2">{{ __('translation.amount') }}:</span>
                                <span class="fs-6 fw-bold text-dark">{{ $settlement->currency ? $settlement->currency->currency_code : 'USD' }}{{ number_format($settlement->total_amount, 2) }}</span>
                            </div>
                            <div class="d-flex align-items-center mt-2">
                                <span class="fs-6 fw-semibold text-gray-400 d-block align-self-start me-2">{{ __('translation.transactions') }}:</span>
                                <span class="fs-6 fw-bold text-dark">{{ $settlement->transaction_count }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Batch Information -->
            @if($settlement->batch)
            <div class="col-md-6">
                <div class="card card-flush h-100">
                    <div class="card-header pt-5">
                        <div class="card-title d-flex flex-column">
                            <div class="d-flex align-items-center">
                                <span class="fs-2hx fw-bold text-dark me-2 lh-1 ls-n2">{{ $settlement->batch->batch_number }}</span>
                            </div>
                            <span class="text-gray-400 pt-1 fw-semibold fs-6">{{ __('translation.related_batch') }}</span>
                        </div>
                    </div>
                    <div class="card-body pt-2 pb-4 d-flex align-items-center flex-wrap">
                        <div class="d-flex flex-column flex-grow-1 pe-8">
                            <div class="d-flex align-items-center">
                                <span class="fs-6 fw-semibold text-gray-400 d-block align-self-start me-2">{{ __('translation.status') }}:</span>
                                @switch($settlement->batch->status)
                                    @case('pending')
                                        <span class="badge badge-light-warning fs-7 fw-bold">{{ __('translation.pending') }}</span>
                                        @break
                                    @case('settled')
                                        <span class="badge badge-light-success fs-7 fw-bold">{{ __('translation.settled') }}</span>
                                        @break
                                    @case('failed')
                                        <span class="badge badge-light-danger fs-7 fw-bold">{{ __('translation.failed') }}</span>
                                        @break
                                    @default
                                        <span class="badge badge-light-secondary fs-7 fw-bold">{{ ucfirst($settlement->batch->status) }}</span>
                                @endswitch
                            </div>
                            <div class="d-flex align-items-center mt-2">
                                <span class="fs-6 fw-semibold text-gray-400 d-block align-self-start me-2">{{ __('translation.amount') }}:</span>
                                <span class="fs-6 fw-bold text-dark">{{ $settlement->currency ? $settlement->currency->currency_code : 'USD' }}{{ number_format($settlement->batch->total_amount, 2) }}</span>
                            </div>
                            <div class="d-flex align-items-center mt-2">
                                <span class="fs-6 fw-semibold text-gray-400 d-block align-self-start me-2">{{ __('translation.transactions') }}:</span>
                                <span class="fs-6 fw-bold text-dark">{{ $settlement->batch->transaction_count }}</span>
                            </div>
                            <div class="d-flex align-items-center mt-2">
                                <a href="{{ route('merchant.batches.show', $settlement->batch) }}" class="btn btn-sm btn-light-primary">
                                    <i class="ki-duotone ki-eye fs-3">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                    </i>
                                    {{ __('translation.view_batch') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Second Row -->
        <div class="row g-5 g-xl-8 mb-5">
            <!-- Merchant Information -->
            @if($settlement->merchant)
            <div class="col-md-6">
                <div class="card card-flush h-100">
                    <div class="card-header pt-5">
                        <div class="card-title d-flex flex-column">
                            <div class="d-flex align-items-center">
                                <span class="fs-2hx fw-bold text-dark me-2 lh-1 ls-n2">{{ $settlement->merchant->name }}</span>
                            </div>
                            <span class="text-gray-400 pt-1 fw-semibold fs-6">{{ __('translation.merchant') }}</span>
                        </div>
                    </div>
                    <div class="card-body pt-2 pb-4 d-flex align-items-center flex-wrap">
                        <div class="d-flex flex-column flex-grow-1 pe-8">
                            <div class="d-flex align-items-center">
                                <span class="fs-6 fw-semibold text-gray-400 d-block align-self-start me-2">{{ __('translation.email') }}:</span>
                                <span class="fs-6 fw-bold text-dark">{{ $settlement->merchant->email }}</span>
                            </div>
                            <div class="d-flex align-items-center mt-2">
                                <span class="fs-6 fw-semibold text-gray-400 d-block align-self-start me-2">{{ __('translation.phone') }}:</span>
                                <span class="fs-6 fw-bold text-dark">{{ $settlement->merchant->phone }}</span>
                            </div>
                            <div class="d-flex align-items-center mt-2">
                                <span class="fs-6 fw-semibold text-gray-400 d-block align-self-start me-2">{{ __('translation.business_type') }}:</span>
                                <span class="fs-6 fw-bold text-dark">{{ $settlement->merchant->business_type }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Settlement Timeline -->
            <div class="col-md-6">
                <div class="card card-flush h-100">
                    <div class="card-header pt-5">
                        <div class="card-title d-flex flex-column">
                            <div class="d-flex align-items-center">
                                <span class="fs-2hx fw-bold text-dark me-2 lh-1 ls-n2">{{ __('translation.timeline') }}</span>
                            </div>
                            <span class="text-gray-400 pt-1 fw-semibold fs-6">{{ __('translation.settlement_history') }}</span>
                        </div>
                    </div>
                    <div class="card-body pt-2 pb-4 d-flex align-items-center flex-wrap">
                        <div class="d-flex flex-column flex-grow-1 pe-8">
                            <div class="d-flex align-items-center">
                                <span class="fs-6 fw-semibold text-gray-400 d-block align-self-start me-2">{{ __('translation.created') }}:</span>
                                <span class="fs-6 fw-bold text-dark">{{ $settlement->created_at->format('M d, Y H:i:s') }}</span>
                            </div>
                            @if($settlement->settled_at)
                            <div class="d-flex align-items-center mt-2">
                                <span class="fs-6 fw-semibold text-gray-400 d-block align-self-start me-2">{{ __('translation.settled') }}:</span>
                                <span class="fs-6 fw-bold text-dark">{{ $settlement->settled_at->format('M d, Y H:i:s') }}</span>
                            </div>
                            @endif
                            @if($settlement->failed_at)
                            <div class="d-flex align-items-center mt-2">
                                <span class="fs-6 fw-semibold text-gray-400 d-block align-self-start me-2">{{ __('translation.failed') }}:</span>
                                <span class="fs-6 fw-bold text-dark">{{ $settlement->failed_at->format('M d, Y H:i:s') }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Settlement Details Card -->
        <div class="card mb-5 mb-xl-8">
            <div class="card-header border-0 pt-5">
                <h3 class="card-title align-items-start flex-column">
                    <span class="card-label fw-bold fs-3 mb-1">{{ __('translation.settlement_details') }}</span>
                    <span class="text-muted mt-1 fw-semibold fs-7">{{ __('translation.complete_settlement_information') }}</span>
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="d-flex flex-column mb-7">
                            <span class="fs-6 fw-semibold mb-2 text-muted">{{ __('translation.settlement_number') }}</span>
                            <span class="fs-5 fw-bold">{{ $settlement->settlement_number }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex flex-column mb-7">
                            <span class="fs-6 fw-semibold mb-2 text-muted">{{ __('translation.status') }}</span>
                            <span class="fs-5 fw-bold">{{ ucfirst($settlement->status) }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex flex-column mb-7">
                            <span class="fs-6 fw-semibold mb-2 text-muted">{{ __('translation.total_amount') }}</span>
                            <span class="fs-5 fw-bold text-success">{{ $settlement->currency ? $settlement->currency->currency_code : 'USD' }}{{ number_format($settlement->total_amount, 2) }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex flex-column mb-7">
                            <span class="fs-6 fw-semibold mb-2 text-muted">{{ __('translation.transaction_count') }}</span>
                            <span class="fs-5 fw-bold">{{ $settlement->transaction_count }}</span>
                        </div>
                    </div>
                    @if($settlement->settlement_reference)
                    <div class="col-md-6">
                        <div class="d-flex flex-column mb-7">
                            <span class="fs-6 fw-semibold mb-2 text-muted">{{ __('translation.settlement_reference') }}</span>
                            <span class="fs-5 fw-bold">{{ $settlement->settlement_reference }}</span>
                        </div>
                    </div>
                    @endif
                    @if($settlement->notes)
                    <div class="col-12">
                        <div class="d-flex flex-column mb-7">
                            <span class="fs-6 fw-semibold mb-2 text-muted">{{ __('translation.notes') }}</span>
                            <span class="fs-5">{{ $settlement->notes }}</span>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Related Transactions Card -->
        @if($settlement->batch && $settlement->batch->transactions->count() > 0)
        @can('transactions') @can('view_transactions')
        <div class="card mb-5 mb-xl-8">
            <div class="card-header border-0 pt-5">
                <h3 class="card-title align-items-start flex-column">
                    <span class="card-label fw-bold fs-3 mb-1">{{ __('translation.related_transactions') }}</span>
                    <span class="text-muted mt-1 fw-semibold fs-7">{{ __('translation.transactions_in_this_settlement') }}</span>
                </h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-row-bordered table-row-gray-100 align-middle gs-0 gy-3">
                        <thead>
                            <tr class="fw-bold text-muted">
                                <th class="min-w-150px">{{ __('translation.transaction_id') }}</th>
                                <th class="min-w-140px">{{ __('translation.amount') }}</th>
                                <th class="min-w-120px">{{ __('translation.status') }}</th>
                                <th class="min-w-120px">{{ __('translation.card_number') }}</th>
                                <th class="min-w-100px">{{ __('translation.created_at') }}</th>
                                @can('view_transactions')
                                <th class="min-w-100px">{{ __('translation.actions') }}</th>
                                @endcan
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($settlement->batch->transactions as $transaction)
                            <tr>
                                <td>
                                    <span class="text-dark fw-bold text-hover-primary fs-6">
                                        {{ $transaction->transaction_id }}
                                    </span>
                                </td>
                                <td>
                                    <span class="text-dark fw-bold text-hover-primary fs-6">
                                        $ {{ number_format($transaction->amount, 2) }}
                                    </span>
                                </td>
                                <td>
                                    @switch($transaction->status)
                                        @case('captured')
                                            <span class="badge badge-light-success">{{ __('translation.captured') }}</span>
                                            @break
                                        @case('declined')
                                            <span class="badge badge-light-danger">{{ __('translation.declined') }}</span>
                                            @break
                                        @default
                                            <span class="badge badge-light-secondary">{{ ucfirst($transaction->status) }}</span>
                                    @endswitch
                                </td>
                                <td>
                                    <span class="text-dark fw-bold text-hover-primary fs-6">
                                        {{ $transaction->card_number }}
                                    </span>
                                </td>
                                <td>
                                    <span class="text-muted fw-semibold text-muted d-block fs-7">
                                        {{ $transaction->created_at->format('M d, Y H:i:s') }}
                                    </span>
                                </td>
                                @can('view_transactions')
                                <td>
                                    <a href="{{ route('merchant.transactions.show', $transaction) }}" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm" data-bs-toggle="tooltip" title="{{ __('translation.view_transaction') }}">
                                        <i class="ki-duotone ki-eye fs-2">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                        </i>
                                    </a>
                                </td>
                                @endcan
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @else
        <div class="card mb-5 mb-xl-8">
            <div class="card-header border-0 pt-5">
                <h3 class="card-title align-items-start flex-column">
                    <span class="card-label fw-bold fs-3 mb-1">{{ __('translation.related_transactions') }}</span>
                    <span class="text-muted mt-1 fw-semibold fs-7">{{ __('translation.transactions_in_this_settlement') }}</span>
                </h3>
            </div>
            <div class="card-body">
                <div class="alert alert-warning d-flex align-items-center p-5">
                    <i class="ki-duotone ki-shield-cross fs-2hx text-warning me-4">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <div class="d-flex flex-column">
                        <h4 class="mb-1 text-warning">{{ __('translation.access_denied') }}</h4>
                        <span>{{ __('translation.no_permission_to_view_transactions') }}</span>
                    </div>
                </div>
            </div>
        </div>
        @endcan @endcan
        @endif
    </div>
    <!--end::Container-->
</div>
@endsection

@push('scripts')
<script>
    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
</script>
@endpush
