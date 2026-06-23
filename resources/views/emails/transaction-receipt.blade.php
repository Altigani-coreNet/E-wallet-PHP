@include('emails.partials.locale')
<!DOCTYPE html>
<html lang="{{ $emailLocale }}" dir="{{ $emailDir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('emails.transaction_receipt_subject') }} - {{ $transaction->transaction_id }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .email-container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #007bff;
            margin: 0;
            font-size: 28px;
        }
        .header p {
            color: #666;
            margin: 5px 0 0 0;
            font-size: 16px;
        }
        .transaction-info {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: bold;
            color: #495057;
        }
        .info-value {
            color: #212529;
        }
        .amount-highlight {
            text-align: center;
            background-color: #e3f2fd;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .amount-highlight .amount {
            font-size: 32px;
            font-weight: bold;
            color: #1976d2;
            margin: 0;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }
        .status-declined {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-voided {
            background-color: #e2e3e5;
            color: #383d41;
        }
        .status-refunded {
            background-color: #cce5ff;
            color: #004085;
        }
        .personal-message {
            background-color: #fff3cd;
            {{ $emailBorderInline }}: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .personal-message h3 {
            margin-top: 0;
            color: #856404;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            color: #6c757d;
            font-size: 14px;
        }
        .card-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
        }
        .card-info h4 {
            margin-top: 0;
            color: #495057;
        }
        @media (max-width: 600px) {
            body {
                padding: 10px;
            }
            .email-container {
                padding: 20px;
            }
            .info-row {
                flex-direction: column;
            }
            .info-label {
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body style="direction: {{ $emailDir }}; text-align: {{ $emailAlign }};">
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <h1>{{ __('emails.transaction_receipt_title') }}</h1>
            <p>{{ $transaction->merchant ? $transaction->merchant->name : __('emails.transaction_merchant') }}</p>
        </div>

        <!-- Personal Message -->
        @if($personalMessage)
        <div class="personal-message">
            <h3>{{ __('emails.transaction_personal_message') }}</h3>
            <p>{{ $personalMessage }}</p>
        </div>
        @endif

        <!-- Attachment Notice -->
        <div class="personal-message" style="background-color: #e3f2fd; border-left-color: #2196f3;">
            <h3>{{ __('emails.transaction_invoice_attachment') }}</h3>
            <p>{{ __('emails.transaction_invoice_attachment_notice') }}</p>
        </div>

        <!-- Transaction Amount -->
        <div class="amount-highlight">
            <p class="amount">$ {{ number_format($transaction->amount, 2) }}</p>
            <p>{{ __('emails.transaction_amount_label') }}</p>
        </div>

        <!-- Transaction Information -->
        <div class="transaction-info">
            <div class="info-row">
                <span class="info-label">{{ __('emails.transaction_id') }}:</span>
                <span class="info-value">{{ $transaction->transaction_id }}</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">{{ __('emails.transaction_status') }}:</span>
                <span class="info-value">
                    <span class="status-badge status-{{ strtolower($transaction->status) }}">
                        {{ $transaction->status }}
                    </span>
                </span>
            </div>
            
            <div class="info-row">
                <span class="info-label">{{ __('emails.transaction_date_time') }}:</span>
                <span class="info-value">{{ $transaction->created_at->format('Y-m-d H:i:s') }}</span>
            </div>
            
            @if($transaction->rrn)
            <div class="info-row">
                <span class="info-label">{{ __('emails.transaction_rrn') }}:</span>
                <span class="info-value">{{ $transaction->rrn }}</span>
            </div>
            @endif
            
            @if($transaction->auth_code)
            <div class="info-row">
                <span class="info-label">{{ __('emails.transaction_approval_code') }}:</span>
                <span class="info-value">{{ $transaction->auth_code }}</span>
            </div>
            @endif
            
            @if($transaction->batch_no)
            <div class="info-row">
                <span class="info-label">{{ __('emails.transaction_batch_no') }}:</span>
                <span class="info-value">{{ $transaction->batch_no }}</span>
            </div>
            @endif
        </div>

        <!-- Card Information -->
        @if($transaction->method || $transaction->card_number)
        <div class="card-info">
            <h4>{{ __('emails.transaction_card_info') }}</h4>
            
            @if($transaction->method)
            <div class="info-row">
                <span class="info-label">{{ __('emails.transaction_payment_method') }}:</span>
                <span class="info-value">{{ ucfirst($transaction->method) }}</span>
            </div>
            @endif
            
            @if($transaction->card_number)
            <div class="info-row">
                <span class="info-label">{{ __('emails.transaction_card_number') }}:</span>
                <span class="info-value">**** **** **** {{ substr($transaction->card_number, -4) }}</span>
            </div>
            @endif
            
            @if($transaction->expiry)
            <div class="info-row">
                <span class="info-label">{{ __('emails.transaction_expiry') }}:</span>
                <span class="info-value">{{ $transaction->expiry }}</span>
            </div>
            @endif
        </div>
        @endif

        <!-- Terminal Information -->
        {{-- @if($transaction->terminal_id)
        <div class="transaction-info">
            <div class="info-row">
                <span class="info-label">{{ __('emails.transaction_terminal') }}:</span>
                <span class="info-value">{{ $transaction->terminal_id }}</span>
            </div>
        </div>
        @endif --}}

        <!-- Footer -->
        <div class="footer">
            <p>{{ __('emails.transaction_thank_you') }}</p>
            <p>{{ __('emails.transaction_keep_receipt') }}</p>
            <p>{{ __('emails.transaction_contact_support') }}</p>
        </div>
    </div>
</body>
</html>
