@extends('admin.layouts.app')

@section('title', 'Settlements for Batch')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Settlements for Batch: {{ $batch->batch_number }}</h3>
        <div class="card-toolbar">
            <a href="{{ route('admin.batches.show', $batch) }}" class="btn btn-secondary">
                <i class="ki-duotone ki-arrow-left fs-3"></i>
                Back to Batch
            </a>
        </div>
    </div>
    
    <div class="card-body">
        @if($settlements->count() > 0)
            <div class="table-responsive">
                <table class="table table-row-bordered table-row-gray-100 align-middle gs-0 gy-3">
                    <thead>
                        <tr class="fw-bold text-muted">
                            <th>Settlement Number</th>
                            <th>Status</th>
                            <th>Amount</th>
                            <th>Transactions</th>
                            <th>Created</th>
                            <th>Settled At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($settlements as $settlement)
                        <tr>
                            <td>
                                <a href="{{ route('admin.settlements.show', $settlement) }}" class="text-dark fw-bold text-hover-primary fs-6">
                                    {{ $settlement->settlement_number }}
                                </a>
                            </td>
                            <td>
                                @switch($settlement->status)
                                    @case('pending')
                                        <span class="badge badge-light-warning">Pending</span>
                                        @break
                                    @case('settled')
                                        <span class="badge badge-light-success">Settled</span>
                                        @break
                                    @case('failed')
                                        <span class="badge badge-light-danger">Failed</span>
                                        @break
                                    @default
                                        <span class="badge badge-light-secondary">{{ ucfirst($settlement->status) }}</span>
                                @endswitch
                            </td>
                            <td>
                                <span class="text-dark fw-bold text-hover-primary fs-6">
                                    ${{ number_format($settlement->total_amount, 2) }}
                                </span>
                            </td>
                            <td>
                                <span class="text-dark fw-bold text-hover-primary fs-6">
                                    {{ $settlement->transaction_count }}
                                </span>
                            </td>
                            <td>
                                <span class="text-muted fw-semibold text-muted d-block fs-7">
                                    {{ $settlement->created_at->format('M d, Y H:i:s') }}
                                </span>
                            </td>
                            <td>
                                <span class="text-muted fw-semibold text-muted d-block fs-7">
                                    {{ $settlement->settled_at ? $settlement->settled_at->format('M d, Y H:i:s') : 'N/A' }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('admin.settlements.show', $settlement) }}" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1">
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
                </table>
            </div>
        @else
            <div class="text-center text-muted py-8">
                <i class="ki-duotone ki-document fs-2hx text-muted mb-3">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
                <div>No settlements found for this batch.</div>
            </div>
        @endif
    </div>
</div>
@endsection
