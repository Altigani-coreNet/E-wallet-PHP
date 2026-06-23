@extends('layouts.admin.admin_layout')

@section('main-head', __('translation.batch_details'))
@section('breadcrumbs')
    <!--begin::Breadcrumb-->
    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">
            <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">{{ __('translation.home') }}</a>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">
            <a href="{{ route('admin.batches.index') }}" class="text-muted text-hover-primary">{{ __('translation.batches') }}</a>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">{{ __('translation.batch_details') }}</li>
        <!--end::Item-->
    </ul>
    <!--end::Breadcrumb-->
@endsection

@section('toolbar_actions')
<div class="d-flex align-items-center gap-2 gap-lg-3">
    <!--begin::Back button-->
    <a href="{{ route('admin.batches.index') }}" class="btn btn-sm btn-flex btn-secondary fw-bold">
        <i class="ki-duotone ki-arrow-left fs-6 text-muted me-1">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
        {{ __('translation.back_to_batches') }}
    </a>
    <!--end::Back button-->
    
    @if($batch->status === 'pending')
    <!--begin::Process Settlement button-->
    <form action="{{ route('admin.batches.process-settlement', $batch) }}" method="POST" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-sm btn-flex btn-success fw-bold" 
                onclick="return confirm('Are you sure you want to process settlement for this batch? This will mark all captured transactions as approved and create a settlement.')">
            <i class="ki-duotone ki-check fs-6 text-white me-1">
                <span class="path1"></span>
                <span class="path2"></span>
            </i>
            Process Settlement
        </button>
    </form>
    <!--end::Process Settlement button-->
    @endif
</div>
@endsection

@section('content')
<div class="row g-5 g-xl-10 mb-5 mb-xl-10">
    <div class="col-md-12">
        <!--begin::Card-->
        <div class="card  ">
            <!--begin::Header-->
            <div class="card-header pt-5">
                <!--begin::Title-->
                <div class="card-title d-flex flex-column">
                    <!--begin::Amount-->
                    <div class="d-flex align-items-center">
                        <span class="fs-2hx fw-bold text-dark me-2 lh-1 ls-n2">{{ $batch->batch_number }}</span>
                    </div>
                    <!--end::Amount-->
                    <!--begin::Label-->
                    <span class="text-gray-400 pt-1 fw-semibold fs-6">{{ __('translation.batch_information') }}</span>
                    <!--end::Label-->
                </div>
                <!--end::Title-->
            </div>
            <!--end::Header-->
            <!--begin::Card body-->
            <div class="card-body pt-2 pb-4 d-flex align-items-center">
                <div class="d-flex flex-column flex-grow-1">
                    <div class="d-flex align-items-center mb-2">
                        <span class="text-gray-600 fw-semibold fs-6 me-2">{{ __('translation.merchant') }}:</span>
                        <span class="text-dark fw-bold fs-6">{{ $batch->merchant->name ?? 'N/A' }}</span>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <span class="text-gray-600 fw-semibold fs-6 me-2">{{ __('translation.status') }}:</span>
                        @php
                            $statusClass = match($batch->status) {
                                'pending' => 'badge-light-warning',
                                'settled' => 'badge-light-success',
                                'failed' => 'badge-light-danger',
                                default => 'badge-light-secondary'
                            };
                        @endphp
                        <span class="badge {{ $statusClass }} fs-7">{{ ucfirst($batch->status) }}</span>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <span class="text-gray-600 fw-semibold fs-6 me-2">{{ __('translation.total_amount') }}:</span>
                        <span class="text-dark fw-bold fs-6">{{ number_format($batch->total_amount, 2) }}</span>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <span class="text-gray-600 fw-semibold fs-6 me-2">{{ __('translation.transaction_count') }}:</span>
                        <span class="text-dark fw-bold fs-6">{{ $batch->transaction_count }}</span>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <span class="text-gray-600 fw-semibold fs-6 me-2">{{ __('translation.created_at') }}:</span>
                        <span class="text-dark fw-bold fs-6">{{ $batch->created_at->format('Y-m-d H:i:s') }}</span>
                    </div>
                    @if($batch->settled_at)
                    <div class="d-flex align-items-center">
                        <span class="text-gray-600 fw-semibold fs-6 me-2">{{ __('translation.settled_at') }}:</span>
                        <span class="text-dark fw-bold fs-6">{{ $batch->settled_at->format('Y-m-d H:i:s') }}</span>
                    </div>
                    @endif
                </div>
            </div>
            <!--end::Card body-->
        </div>
        <!--end::Card-->
    </div>
</div>

<!--begin::Card-->
<div class="card">
    <!--begin::Card header-->
    <div class="card-header border-0 pt-6">
        <!--begin::Card title-->
        <div class="card-title">
            <h3 class="card-title">{{ __('translation.transactions') }}</h3>
        </div>
        <!--end::Card title-->
    </div>
    <!--end::Card header-->
    <!--begin::Card body-->
    <div class="card-body pt-0">
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5" id="transactions-table">
                <!--begin::Table head-->
                <thead>
                    <!--begin::Table row-->
                    <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                        <th class="text-dark">{{ __('translation.transaction_id') }}</th>
                        <th class="text-dark">{{ __('translation.amount') }}</th>
                        {{-- <th class="text-dark">{{ __('translation.currency') }}</th> --}}
                        <th class="text-dark">{{ __('translation.status') }}</th>
                        <th class="text-dark">{{ __('translation.terminal') }}</th>
                        <th class="text-dark">{{ __('translation.created_at') }}</th>
                    </tr>
                    <!--end::Table row-->
                </thead>
                <!--end::Table head-->
                <!--begin::Table body-->
                <tbody class="fw-semibold text-gray-600">
                    {{-- @dd($batch->transactions) --}}
                    @forelse($batch->transactions as $transaction)
                    <tr>
                        <td class="text-dark fw-bold">{{ $transaction->transaction_id }}</td>
                        <td class="text-dark fw-bold">$ {{ number_format($transaction->amount, 2) }}</td>
                        {{-- <td class="text-dark fw-bold">{{ $transaction->currency }}</td> --}}
                        {{-- @dd($transaction->status) --}}
                        <td>
                            @php
                                $statusClass = match($transaction->status) {
                                    'pending' => 'badge-light-warning',
                                    'approved' => 'badge-light-success',
                                    'captured' => 'badge-light-success',
                                    'declined' => 'badge-light-danger',
                                    'failed' => 'badge-light-danger',
                                    'voided' => 'badge-light-danger',
                                    'refunded' => 'badge-light-danger',
                                    'cancelled' => 'badge-light-danger',
                                    'expired' => 'badge-light-danger',
                                    'reversed' => 'badge-light-danger',
                                    default => 'badge-light-secondary'
                                };
                            @endphp
                            <span class="badge {{ $statusClass }} fs-7">{{ ucfirst($transaction->status) }}</span>
                        </td>
                        <td class="text-dark fw-bold">{{ $transaction->terminal_id ?? 'N/A' }}</td>
                        <td class="text-dark fw-bold">{{ $transaction->created_at->format('Y-m-d H:i:s') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted fs-6 py-8">
                            <i class="ki-duotone ki-document fs-2hx text-muted mb-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <div>{{ __('translation.no_transactions_found') }}</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                <!--end::Table body-->
            </table>
        </div>
        <!--end::Table-->
    </div>
    <!--end::Card body-->
</div>
<!--end::Card-->

<!--begin::Settlements Card-->
@if($batch->settlements && $batch->settlements->count() > 0)
<div class="card mt-5">
    <!--begin::Card header-->
    <div class="card-header border-0 pt-6">
        <!--begin::Card title-->
        <div class="card-title">
            <h3 class="card-title">Settlements</h3>
        </div>
        <!--end::Card title-->
        <!--begin::Card toolbar-->
        <div class="card-toolbar">
            <a href="{{ route('admin.settlements.by-batch', $batch) }}" class="btn btn-sm btn-light-primary">
                <i class="ki-duotone ki-eye fs-6 text-muted me-1">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
                View All Settlements
            </a>
        </div>
        <!--end::Card toolbar-->
    </div>
    <!--end::Card header-->
    <!--begin::Card body-->
    <div class="card-body pt-0">
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5">
                <!--begin::Table head-->
                <thead>
                    <!--begin::Table row-->
                    <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                        <th class="text-dark">Settlement Number</th>
                        <th class="text-dark">Status</th>
                        <th class="text-dark">Amount</th>
                        <th class="text-dark">Transactions</th>
                        <th class="text-dark">Settled At</th>
                        <th class="text-dark">Actions</th>
                    </tr>
                    <!--end::Table row-->
                </thead>
                <!--end::Table head-->
                <!--begin::Table body-->
                <tbody class="fw-semibold text-gray-600">
                    @foreach($batch->settlements as $settlement)
                    <tr>
                        <td class="text-dark fw-bold">{{ $settlement->settlement_number }}</td>
                        <td>
                            @php
                                $statusClass = match($settlement->status) {
                                    'pending' => 'badge-light-warning',
                                    'settled' => 'badge-light-success',
                                    'failed' => 'badge-light-danger',
                                    default => 'badge-light-secondary'
                                };
                            @endphp
                            <span class="badge {{ $statusClass }} fs-7">{{ ucfirst($settlement->status) }}</span>
                        </td>
                        <td class="text-dark fw-bold">${{ number_format($settlement->total_amount, 2) }}</td>
                        <td class="text-dark fw-bold">{{ $settlement->transaction_count }}</td>
                        <td class="text-dark fw-bold">{{ $settlement->settled_at ? $settlement->settled_at->format('Y-m-d H:i:s') : 'N/A' }}</td>
                        <td>
                            <a href="{{ route('admin.settlements.show', $settlement) }}" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm">
                                <i class="ki-duotone ki-eye fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                </i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <!--end::Table body-->
            </table>
        </div>
        <!--end::Table-->
    </div>
    <!--end::Card body-->
</div>
@endif
<!--end::Settlements Card-->
@endsection
