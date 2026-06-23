@extends('layouts.admin.admin_layout')
@section('main-head', __('translation.edit_payment_link'))

@section('breadcrumbs')
    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
        <li class="breadcrumb-item text-muted">
            <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">{{ __('translation.home') }}</a>
        </li>
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <li class="breadcrumb-item text-muted">
            <a href="{{ route('admin.payment-links.index') }}" class="text-muted text-hover-primary">{{ __('translation.payment_links') }}</a>
        </li>
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <li class="breadcrumb-item text-muted">{{ __('translation.edit_payment_link') }}</li>
    </ul>
@endsection

@section('content')
    <div class="post d-flex flex-column-fluid" id="kt_post">
        <div id="kt_content_container" class="container-xxl">
            <div class="row g-5 g-xl-8 mt-4"></div>
            <div class="row">
                <div class="col-md-12" id="form-section">
                    <div class="card">
                        <div class="card-header border-0 pt-6 d-flex justify-content-between align-items-center">
                            <div class="card-title">
                                <h3 class="card-label">{{ __('translation.edit_payment_link') }}</h3>
                            </div>
                        </div>
                        <form id="payment-link-form" action="{{ route('admin.payment-links.update', $paymentLink->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="card-body pt-0">
                                @if ($errors->any())
                                    <div class="alert alert-danger mb-4">
                                        <div class="fw-bold mb-2">There were some errors with your submission.</div>
                                        <ul class="mb-0 ps-3">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="merchant_id" class="form-label">{{ __('translation.merchant') }}</label>
                                        <select class="form-control has_select_2 @error('merchant_id') is-invalid @enderror" name="merchant_id" id="merchant_id" data-url="{{ route('merchants.select') }}" data-placeholder="Select a merchant" required>
                                            <option value="">----</option>
                                            @if ($paymentLink->merchant)
                                                <option value="{{ $paymentLink->merchant->id }}" selected>
                                                    {{ $paymentLink->merchant->business_name }}
                                                </option>
                                            @endif
                                        </select>
                                        @error('merchant_id')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6 mt-1">
                                        <div class="d-flex justify-content-between">
                                            <label for="customer_id" class="form-label">{{ __('translation.customer') }}</label>
                                            <a href="#" class="" data-bs-toggle="modal" data-bs-target="#addCustomerModal">Add Customer</a>
                                        </div>
                                        <select class="form-control has_select_2 @error('customer_id') is-invalid @enderror" name="customer_id" id="customer_id" data-url="{{ route('admin.customers.select') }}" data-placeholder="Select a customer">
                                            <option value="">----</option>
                                            @if($paymentLink->customer)
                                                <option value="{{ $paymentLink->customer->id }}" selected>
                                                    {{ $paymentLink->customer->name }} ({{ $paymentLink->customer->email }})
                                                </option>
                                            @endif
                                        </select>
                                        @error('customer_id')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="amount" class="form-label">{{ __('translation.amount') }}</label>
                                        <input type="number" step="0.01" class="form-control @error('amount') is-invalid @enderror" id="amount" name="amount" value="{{ old('amount', $paymentLink->amount) }}" required>
                                        @error('amount')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="currency_id" class="form-label">{{ __('translation.currency') }}</label>
                                        <select class="form-control has_select_2 @error('currency_id') is-invalid @enderror" id="currency_id" name="currency_id" data-url="{{ route('currencies.select') }}" data-placeholder="Select a currency" required>
                                            <option value="">----</option>
                                            @if ($paymentLink->currency)
                                                <option value="{{ $paymentLink->currency->id }}" selected>
                                                    {{ $paymentLink->currency->currency_code }}
                                                </option>
                                            @endif
                                        </select>
                                        @error('currency_id')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    @php
                                        // Ensure payment_method_types is always an array
                                        $paymentMethodTypes = old('payment_method_types', $paymentLink->payment_method_types ?? []);
                                        if (!is_array($paymentMethodTypes)) {
                                            $paymentMethodTypes = is_string($paymentMethodTypes) ? json_decode($paymentMethodTypes, true) : [];
                                        }
                                        if (!is_array($paymentMethodTypes)) {
                                            $paymentMethodTypes = [];
                                        }
                                    @endphp
                                    <div class="col-md-6">
                                        <label for="payment_method_types" class="form-label">{{ __('translation.payment_method_types') }}</label>
                                        <select class="form-control has_select_3 @error('payment_method_types') is-invalid @enderror @error('payment_method_types.*') is-invalid @enderror" name="payment_method_types[]" id="payment_method_types" multiple="multiple" data-placeholder="Select payment methods">
                                            <option value="card" {{ in_array('card', $paymentMethodTypes) ? 'selected' : '' }}>cards</option>
                                            <option value="afterpay_clearpay" {{ in_array('afterpay_clearpay', $paymentMethodTypes) ? 'selected' : '' }}>buy now, pay later</option>
                                            <option value="alipay" {{ in_array('alipay', $paymentMethodTypes) ? 'selected' : '' }}>Alipay</option>
                                            <option value="bancontact" {{ in_array('bancontact', $paymentMethodTypes) ? 'selected' : '' }}>Bancontact</option>
                                            <option value="eps" {{ in_array('eps', $paymentMethodTypes) ? 'selected' : '' }}>EPS</option>
                                            <option value="giropay" {{ in_array('giropay', $paymentMethodTypes) ? 'selected' : '' }}>giropay</option>
                                            <option value="grabpay" {{ in_array('grabpay', $paymentMethodTypes) ? 'selected' : '' }}>GrabPay</option>
                                            <option value="ideal" {{ in_array('ideal', $paymentMethodTypes) ? 'selected' : '' }}>iDEAL</option>
                                            <option value="klarna" {{ in_array('klarna', $paymentMethodTypes) ? 'selected' : '' }}>Klarna</option>
                                            <option value="oxxo" {{ in_array('oxxo', $paymentMethodTypes) ? 'selected' : '' }}>OXXO vouchers</option>
                                            <option value="p24" {{ in_array('p24', $paymentMethodTypes) ? 'selected' : '' }}>Przelewy24</option>
                                            <option value="sepa_debit" {{ in_array('sepa_debit', $paymentMethodTypes) ? 'selected' : '' }}>SEPA Direct Debit</option>
                                            <option value="sofort" {{ in_array('sofort', $paymentMethodTypes) ? 'selected' : '' }}>Sofort</option>
                                            <option value="us_bank_account" {{ in_array('us_bank_account', $paymentMethodTypes) ? 'selected' : '' }}>ACH direct debit</option>
                                            <option value="wechat_pay" {{ in_array('wechat_pay', $paymentMethodTypes) ? 'selected' : '' }}>WeChat Pay</option>
                                        </select>
                                        @error('payment_method_types')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                        @error('payment_method_types.*')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="scheduled_date" class="form-label">{{ __('translation.scheduled_date') }}</label>
                                        <input type="date" class="form-control @error('scheduled_date') is-invalid @enderror" id="scheduled_date" name="scheduled_date" value="{{ old('scheduled_date', $paymentLink->scheduled_date ? \Carbon\Carbon::parse($paymentLink->scheduled_date)->format('Y-m-d') : '') }}">
                                        @error('scheduled_date')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="expired_date" class="form-label">Link Expired Date</label>
                                        <input type="date" class="form-control @error('expired_date') is-invalid @enderror" id="expired_date" name="expired_date" value="{{ old('expired_date', $paymentLink->expired_date ? \Carbon\Carbon::parse($paymentLink->expired_date)->format('Y-m-d') : '') }}">
                                        @error('expired_date')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                </div>
                                <div id="demo-error-message" class="text-danger mt-3" style="display:none;"></div>
                            </div>
                            <div class="card-footer p-3">
                                <button type="submit" class="btn btn-primary">{{ __('translation.save_changes') }}</button>
                                <a href="{{ route('admin.payment-links.index') }}" class="btn btn-secondary">{{ __('translation.cancel') }}</a>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-md-12 d-flex align-items-center justify-content-center d-none" id="iframe-section" style="background: #fff; display: none;">
                    <div style="position: relative; width: 100%; max-width: 320px; aspect-ratio: 430/932; border-radius: 36px; background: linear-gradient(160deg, #232526 0%, #414345 100%); box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37); display: flex; flex-direction: column; align-items: center; justify-content: flex-start; margin: 0 auto;">
                        <div style="position: absolute; top: 2.5%; left: 50%; transform: translateX(-50%); width: 28%; height: 4%; background: #111; border-radius: 16px; z-index: 2;"></div>
                        <div style="margin-top: 7.5%; width: 90%; height: 80%; background: #fff; border-radius: 20px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); position: relative; z-index: 1; display: flex; align-items: stretch;">
                            <iframe id="bank-demo-iframe" src="https://example.com" style="width: 100%; height: 100%; border: none;" allowfullscreen></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Add Customer Modal -->
    <div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('admin.customers.ajax.store') }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addCustomerModalLabel">Add Customer</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="modal_merchant_id" class="form-label">Merchant</label>
                            <select class="form-control has_select_merchant_modal" id="modal_merchant_id" name="merchant_id" data-url="{{ route('merchants.select') }}" data-placeholder="Select Merchant" required>
                                <option value="">Select Merchant</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="modal_customer_name" class="form-label">Customer Name</label>
                            <input type="text" class="form-control" id="modal_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="model_phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="modal_phone" name="phone" required>
                        </div>
                        <div class="mb-3">
                            <label for="modal_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="modal_email" name="email" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Customer</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#customer_id').select2({
            ajax: {
                url: $('#customer_id').data('url'),
                type: 'get',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        search: params.term
                    };
                },
                processResults: function (response) {
                    return {
                        results: response
                    };
                },
                cache: true
            },
            placeholder: $('#customer_id').data('placeholder'),
            allowClear: true
        });
        $('#currency_id').select2({
            ajax: {
                url: $('#currency_id').data('url'),
                type: 'get',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        search: params.term
                    };
                },
                processResults: function(response) {
                    return {
                        results: response
                    };
                },
                cache: true
            },
            placeholder: $('#currency_id').data('placeholder'),
            allowClear: true
        });
        $('#merchant_id').select2({
            ajax: {
                url: $('#merchant_id').data('url'),
                type: 'get',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        search: params.term
                    };
                },
                processResults: function(response) {
                    return {
                        results: response
                    };
                },
                cache: true
            },
            placeholder: $('#merchant_id').data('placeholder'),
            allowClear: true
        });
        $('#payment_method_types').select2({
            placeholder: $('#payment_method_types').data('placeholder'),
            allowClear: true
        });
        // Merchant select2 for modal
        $('#modal_merchant_id').select2({
            dropdownParent: $('#addCustomerModal'),
            ajax: {
                url: $('#modal_merchant_id').data('url'),
                type: 'get',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        search: params.term
                    };
                },
                processResults: function (response) {
                    return {
                        results: response
                    };
                },
                cache: true
            },
            placeholder: $('#modal_merchant_id').data('placeholder'),
            allowClear: true
        });
        // AJAX submit for Add Customer modal
        $('#addCustomerModal form').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            $submitBtn.prop('disabled', true);
            var formData = $form.serialize();
            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: formData,
                success: function(response) {
                    if(response && response.id && response.text) {
                        // Add new customer to select2 and select it
                        var newOption = new Option(response.text, response.id, true, true);
                        $('#customer_id').append(newOption).trigger('change');
                        $('#addCustomerModal').modal('hide');
                        $form[0].reset();
                        // Optionally, reset select2 fields in modal
                        $('#modal_merchant_id').val(null).trigger('change');
                    } else if(response && response.message) {
                        alert(response.message);
                    }
                },
                error: function(xhr) {
                    var msg = 'An error occurred.';
                    if(xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    alert(msg);
                },
                complete: function() {
                    $submitBtn.prop('disabled', false);
                }
            });
        });
        // Demo Bank URL logic
        $('#demo-bank-url').on('click', function(e) {
            e.preventDefault();
            var amount = $('#amount').val();
            var currency = $('#currency').val();
            if (!amount || !currency) {
                $('#demo-error-message').text('Amount and currency are required.').show();
                return;
            } else {
                $('#demo-error-message').hide();
            }
            // Resize sections
            // $('#form-section').removeClass('col-md-12').addClass('col-md-9');
            // $('#iframe-section').removeClass('col-md-12').addClass('col-md-3').removeClass('d-none').show();
            // var form = $('#payment-link-form');
            // var formData = form.serialize();
            // $.ajax({
            //     url: '{{ route('generateStripeSessionUrl') }}',
            //     type: 'post',
            //     data: formData,
            //     success: function(response) {
            //         if(response.url) {
            //             $('#bank-demo-iframe').attr('src', response.url);
            //         } else {
            //             alert('No URL returned');
            //         }
            //     },
            //     error: function() {
            //         alert('Failed to generate demo URL');
            //     }
            // });
        });
    });
</script>
@endpush
