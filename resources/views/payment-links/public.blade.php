<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('translation.payment_link') }} - {{ $link->amount }} {{ $link->currency }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .payment-card { max-width: 500px; margin: 50px auto; }
        .amount { font-size: 2.5rem; font-weight: bold; color: #28a745; }
        .status-badge { font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="payment-card">
            <div class="card shadow">
                <div class="card-header bg-primary text-white text-center">
                    <h4 class="mb-0">{{ __('translation.payment_link') }}</h4>
                </div>
                <div class="card-body text-center p-4">
                    @if($link->status === 'expired')
                        <div class="alert alert-warning">
                            <h5>{{ __('translation.payment_link_expired') }}</h5>
                            <p class="mb-0">{{ __('translation.contact_merchant_for_new_link') }}</p>
                        </div>
                    @elseif($link->status === 'completed')
                        <div class="alert alert-success">
                            <h5>{{ __('translation.payment_completed') }}</h5>
                            <p class="mb-0">{{ __('translation.payment_already_processed') }}</p>
                        </div>
                    @else
                        <div class="mb-4">
                            <div class="amount">{{ $link->amount }} {{ $link->currency }}</div>
                            <p class="text-muted">{{ __('translation.amount_to_pay') }}</p>
                        </div>
                        
                        <div class="mb-4">
                            <span class="badge status-badge bg-{{ $link->status === 'pending' ? 'info' : ($link->status === 'scheduled' ? 'warning' : 'success') }}">
                                {{ __('translation.' . $link->status) }}
                            </span>
                        </div>
                        
                        @if($link->scheduled_date)
                            <div class="mb-3">
                                <small class="text-muted">{{ __('translation.scheduled_for') }}: {{ \Carbon\Carbon::parse($link->scheduled_date)->format('M d, Y H:i') }}</small>
                            </div>
                        @endif
                        
                        @if($link->expired_date)
                            <div class="mb-3">
                                <small class="text-muted">{{ __('translation.expires_on') }}: {{ \Carbon\Carbon::parse($link->expired_date)->format('M d, Y H:i') }}</small>
                            </div>
                        @endif
                        
                        <div class="d-grid">
                            <a href="{{ $link->link }}" class="btn btn-primary btn-lg">
                                {{ __('translation.proceed_to_payment') }}
                            </a>
                        </div>
                        
                        <div class="mt-3">
                            <small class="text-muted">
                                {{ __('translation.payment_link_id') }}: {{ $link->short_uuid }}
                            </small>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
