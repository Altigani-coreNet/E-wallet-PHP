@extends("layouts.admin.admin_layout")
@section('main-head' , __('translation.transaction_details'))
@section('breadcrumb')
@section('breadcrumbs')
    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
        <li class="breadcrumb-item text-muted">
            <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">{{ __('translation.home') }}</a>
        </li>
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <li class="breadcrumb-item text-muted">
            <a href="{{ route('admin.transactions.index') }}" class="text-muted text-hover-primary">{{ __('translation.transactions') }}</a>
        </li>
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <li class="breadcrumb-item text-muted">{{ $transaction->transaction_id }}</li>
    </ul>
@endsection

@section('toolbar_actions')
<div class="d-flex align-items-center gap-2 gap-lg-3">
    <button class="btn btn-sm btn-light btn-active-light-primary" id="send-receipt-btn">
        <i class="ki-duotone ki-message-text-2 fs-3"></i>
        {{ __('translation.send_receipt') }}
    </button>
    
    <button class="btn btn-sm btn-light btn-active-light-primary" id="view-receipt-btn">
        <i class="ki-duotone ki-eye fs-3"></i>
        {{ __('translation.view_receipt') }}
    </button>
    
    <button class="btn btn-sm btn-warning" id="refund-btn" 
            {{ !in_array($transaction->status, [ 'approved', 'settled']) ? 'disabled' : '' }} data-bs-toggle="modal" data-bs-target="#refundModal">
        <i class="ki-duotone ki-arrow-left fs-3"></i>
        {{ __('translation.refund') }}
    </button>
    
    <button class="btn btn-sm btn-danger" id="void-btn"
            {{ !in_array($transaction->status, ['authorized','captured' ,'pending']) ? 'disabled' : '' }}>
        <i class="ki-duotone ki-cross fs-3"></i>
        {{ __('translation.void') }}
    </button>
</div>

@endsection

@section('content')
<div class="post d-flex flex-column-fluid" id="kt_post">
    <div id="kt_content_container" class="container-xxl">
        
        <!-- Transaction Status Card -->
        <div class="row g-5 g-xl-8 mt-4">
            <div class="col-md-12">
                <div class="card bg-light-{{ $transaction->status === __('translation.approved') ? 'success' : ($transaction->status === __('translation.declined') ? 'danger' : 'warning') }} hoverable card-xl-stretch mb-xl-8">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <div class="text-black fw-bolder fs-2 mb-2">
                                    {{ $transaction->status }}
                                    @if($transaction->status === __('translation.approved'))
                                        <span class="badge badge-light-success fs-6 ms-2">{{ __('translation.badge_number') }}</span>
                                    @endif
                                </div>
                                <div class="fw-bold text-black">{{ $transaction->transaction_type }} - {{ $transaction->transaction_id }}</div>
                                <div class="text-muted fs-6">{{ $transaction->created_at->format('Y-m-d H:i:s') }} ({{ __('translation.gmt_plus_4') }})</div>
                            </div>
                            <div class="text-end">
                                <div class="text-black fw-bolder fs-1 mb-2">$ {{ number_format($transaction->amount, 2) }}</div>
                                <div class="fw-bold text-black">USD</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row gx-9 gy-6">
            <!-- Card Information Section -->
            <div class="col-xl-6">
                <div class="card card-dashed h-xl-100 flex-row flex-stack flex-wrap p-6">
                    <div class="d-flex flex-column py-2">
                        <div class="d-flex align-items-center fs-4 fw-bolder mb-5">
                            @if($transaction->paymentMethod)
                                {{ $transaction->paymentMethod->cardholder_name }}
                                <span class="badge badge-light-primary fs-7 ms-2">{{ $transaction->transaction_type }}</span>
                            @else
                                {{ __('translation.card_information') }}
                            @endif
                        </div>
                        
                        <div class="d-flex align-items-center">
                            @if($transaction->method)
                                @if(strtolower($transaction->method) == 'visa')
                                    <img src="{{asset('assets/media/svg/card-logos/visa.svg')}}" alt="" class="me-4">
                                @elseif(strtolower($transaction->method) == 'mastercard')
                                    <img src="{{asset('assets/media/svg/card-logos/mastercard.svg')}}" alt="" class="me-4">
                                @elseif(strtolower($transaction->method) == 'american_express')
                                    <img src="{{asset('assets/media/svg/card-logos/american-express.svg')}}" alt="" class="me-4">
                                @else
                                    <div class="me-4 w-50px h-35px bg-light-primary rounded d-flex align-items-center justify-content-center">
                                        <i class="ki-duotone ki-credit-cart fs-2x text-primary"></i>
                                    </div>
                                @endif
                            @endif
                            
                            <div>
                                <div class="fs-4 fw-bolder">
                                    {{ $transaction->method ? ucfirst($transaction->method) : __('translation.card') }}
                                    @if($transaction->card_number)
                                        **** {{ substr($transaction->card_number, -4) }}
                                    @endif
                                </div>
                                <div class="fs-6 fw-bold text-gray-400">
                                    @if($transaction->expiry)
                                        {{ __('translation.card_expires_at') }} {{ $transaction->expiry }}
                                    @else
                                        {{ __('translation.expiry_information_not_available') }}
                                    @endif
                                </div>
                                @if($transaction->paymentMethod)
                                    <div class="fs-7 text-muted">
                                        {{ __('translation.entry_mode') }}: {{ $transaction->paymentMethod->entry_mode }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transaction Details Section -->
            <div class="col-xl-6">
                <div class="card card-dashed h-xl-100 flex-row flex-stack flex-wrap p-6">
                    <div class="d-flex flex-column py-2 w-100">
                        <div class="fs-4 fw-bolder mb-5">{{ __('translation.transaction_details') }}</div>
                        
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="fs-7 text-muted">{{ __('translation.rrn_id') }}</div>
                                <div class="fs-6 fw-bold">{{ $transaction->rrn ?: __('translation.not_available') }}</div>
                            </div>
                            <div class="col-6">
                                <div class="fs-7 text-muted">{{ __('translation.batch_no') }}</div>
                                <div class="fs-6 fw-bold">{{ $transaction->batch_no ?: __('translation.not_available') }}</div>
                            </div>
                            <div class="col-6">
                                <div class="fs-7 text-muted">{{ __('translation.trace') }}</div>
                                <div class="fs-6 fw-bold">{{ $transaction->trace_no ?: __('translation.not_available') }}</div>
                            </div>
                            <div class="col-6">
                                <div class="fs-7 text-muted">{{ __('translation.approval_code') }}</div>
                                <div class="fs-6 fw-bold">{{ $transaction->auth_code ?: __('translation.not_available') }}</div>
                            </div>
                            <div class="col-6">
                                <div class="fs-7 text-muted">{{ __('translation.device_alias') }}</div>
                                <div class="fs-6 fw-bold">{{ $transaction->terminal_id ?? __('translation.not_available') }}</div>
                            </div>
                            <div class="col-6">
                                <div class="fs-7 text-muted">{{ __('translation.sdk_id') }}</div>
                                <div class="fs-6 fw-bold">{{ $transaction->sdk ?: __('translation.not_available') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Transaction Information -->
        <div class="row gx-9 gy-6 mt-4">
            <div class="col-xl-12"> 
                <div class="card card-dashed h-xl-100 flex-row flex-stack flex-wrap p-6">
                    <div class="d-flex flex-column py-2 w-100">
                        <div class="fs-4 fw-bolder mb-5">{{ __('translation.additional_information') }}</div>
                        
                        <div class="row g-4">
                            <div class="col-md-3">
                                <div class="fs-7 text-muted">{{ __('translation.merchant') }}</div>
                                <div class="fs-6 fw-bold">{{ $transaction->merchant ? $transaction->merchant->name : __('translation.not_available') }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="fs-7 text-muted">{{ __('translation.terminal') }}</div>
                                <div class="fs-6 fw-bold">{{ $transaction->terminal_id ?? __('translation.not_available') }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="fs-7 text-muted">{{ __('translation.invoice_no') }}</div>
                                <div class="fs-6 fw-bold">{{ $transaction->invoice_no ?: __('translation.not_available') }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="fs-7 text-muted">{{ __('translation.linked_trans') }}</div>
                                <div class="fs-6 fw-bold">{{ __('translation.no_linked_transaction') }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="fs-7 text-muted">{{ __('translation.geo_fence_result') }}</div>
                                <div class="fs-6 fw-bold">{{ __('translation.not_available') }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="fs-7 text-muted">{{ __('translation.mid') }}</div>
                                <div class="fs-6 fw-bold">{{ $transaction->mid ?: __('translation.not_available') }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="fs-7 text-muted">{{ __('translation.tid') }}</div>
                                <div class="fs-6 fw-bold">{{ $transaction->tid ?: __('translation.not_available') }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="fs-7 text-muted">{{ __('translation.atc') }}</div>
                                <div class="fs-6 fw-bold">{{ $transaction->atc ?: __('translation.not_available') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
<!-- Refund Modal -->
<div class="modal fade" id="refundModal" tabindex="-1" aria-labelledby="refundModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="refundModalLabel">{{ __('translation.refund_transaction') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="refund-form">
        <div class="modal-body">
          <div class="mb-3 d-flex justify-content-between align-items-center">
            <span class="fw-bold">{{ __('translation.transaction_id') }}</span>
            <span class="badge bg-light-primary">{{ $transaction->transaction_id }}</span>
          </div>
          <div class="mb-3 d-flex justify-content-between align-items-center">
            <span class="fw-bold">{{ __('translation.amount') }}</span>
            <span class="badge bg-light-info">$ {{ number_format($transaction->amount, 2) }}</span>
          </div>
          <div class="mb-3 d-flex justify-content-between align-items-center">
            <span class="fw-bold">{{ __('translation.refundable_amount') }}</span>
            <span class="badge bg-light-success">$ {{ number_format($transaction->refundable_amount ?? $transaction->amount, 2) }}</span>
          </div>
          <div class="mb-3">
            <label for="refund-amount" class="form-label">{{ __('translation.refund_amount') }}</label>
            <input type="number" step="0.01" min="0.01" max="{{ $transaction->refundable_amount ?? $transaction->amount }}" class="form-control" id="refund-amount" name="refund_amount" required>
          </div>
          <div class="mb-3">
            <label for="refund-reason" class="form-label">{{ __('translation.refund_reason') }}</label>
            <textarea class="form-control" id="refund-reason" name="refund_reason" rows="2" required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('translation.cancel') }}</button>
          <button type="submit" class="btn btn-warning">{{ __('translation.refund') }}</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
    $('#send-receipt-btn').on('click', function() {
        Swal.fire({
            title: '{{ __("translation.send_receipt") }}',
            text: '{{ __("translation.send_receipt_confirmation") }}',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '{{ __("translation.send") }}',
            cancelButtonText: '{{ __("translation.cancel") }}'
        }).then((result) => {
            if (result.isConfirmed) {
                toastr.success('{{ __("translation.receipt_sent_successfully") }}');
            }
        });
    });

    $('#view-receipt-btn').on('click', function() {
        window.open('{{ route("admin.transactions.receipt", $transaction->id) }}', '_blank');
    });

    // Remove old refund-btn click handler (SweetAlert)
    // Add refund form submit handler
    $('#refund-form').on('submit', function(e) {
        e.preventDefault();
        const amount = $('#refund-amount').val();
        const reason = $('#refund-reason').val();
        $.ajax({
            url: '{{ route("admin.transactions.refund", $transaction->id) }}',
            type: 'POST',
            headers: {
                'Accept': 'application/json',
            },
            data: {
                amount: amount,
                reason: reason,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                toastr.success('{{ __('translation.refund_initiated') }}');
                $('#refundModal').modal('hide');
                location.reload();
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || '{{ __('translation.refund_failed') }}');
            }
        });
    });

    $('#void-btn').on('click', function() {
        Swal.fire({
            title: '{{ __("translation.void_transaction") }}',
            text: '{{ __("translation.void_confirmation") }}',
            icon: 'warning',
            input: 'textarea',
            inputLabel: '{{ __("translation.void_reason") }}',
            inputPlaceholder: '{{ __("translation.enter_void_reason") }}',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '{{ __("translation.void") }}',
            cancelButtonText: '{{ __("translation.cancel") }}'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("admin.transactions.void", $transaction->id) }}',
                    type: 'POST',
                    data: {
                        reason: result.value,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        toastr.success('{{ __("translation.transaction_voided") }}');
                        location.reload();
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON?.message || '{{ __("translation.transaction_void_failed") }}');
                    }
                });
            }
        });
    });
</script>
@endpush
