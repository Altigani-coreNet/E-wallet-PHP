<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('translation.transaction_invoice') }} - {{ $transaction->transaction_id }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            background-color: #fff;
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border: 1px solid #ddd;
        }
        .invoice-header {
            text-align: center;
            border-bottom: 3px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .invoice-title {
            font-size: 32px;
            font-weight: bold;
            color: #007bff;
            margin: 0;
        }
        .invoice-subtitle {
            font-size: 16px;
            color: #666;
            margin: 5px 0 0 0;
        }
        .invoice-number {
            font-size: 14px;
            color: #999;
            margin-top: 10px;
        }
        .invoice-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .merchant-info, .transaction-info {
            flex: 1;
            padding: 0 20px;
        }
        .merchant-info {
            border-right: 1px solid #eee;
        }
        .info-section h3 {
            color: #007bff;
            margin-bottom: 15px;
            font-size: 18px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 5px;
        }
        .info-row {
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
        }
        .info-label {
            font-weight: bold;
            color: #555;
        }
        .info-value {
            color: #333;
        }
        .amount-section {
            text-align: center;
            background-color: #f8f9fa;
            padding: 30px;
            border-radius: 8px;
            margin: 30px 0;
            border: 2px solid #e9ecef;
        }
        .amount-label {
            font-size: 18px;
            color: #666;
            margin-bottom: 10px;
        }
        .amount-value {
            font-size: 48px;
            font-weight: bold;
            color: #007bff;
            margin: 0;
        }
        .status-section {
            text-align: center;
            margin: 20px 0;
        }
        .status-badge {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 16px;
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
        .transaction-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .transaction-table th,
        .transaction-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .transaction-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #555;
        }
        .transaction-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .card-info {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #007bff;
        }
        .card-info h4 {
            margin-top: 0;
            color: #007bff;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e9ecef;
            color: #666;
            font-size: 14px;
        }
        .footer p {
            margin: 5px 0;
        }
        .terms {
            font-size: 12px;
            color: #999;
            margin-top: 20px;
            text-align: justify;
            line-height: 1.4;
        }
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            .invoice-container {
                border: none;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Invoice Header -->
        <div class="invoice-header">
            <h1 class="invoice-title">{{ __('translation.transaction_invoice') }}</h1>
            <p class="invoice-subtitle">{{ $transaction->merchant ? $transaction->merchant->name : __('translation.merchant') }}</p>
            <p class="invoice-number">{{ __('translation.invoice_number') }}: {{ $transaction->transaction_id }}</p>
        </div>

        <!-- Invoice Details -->
        <div class="invoice-details">
            <div class="merchant-info">
                <div class="info-section">
                    <h3>{{ __('translation.merchant_information') }}</h3>
                    <div class="info-row">
                        <span class="info-label">{{ __('translation.merchant') }}:</span>
                        <span class="info-value">{{ $transaction->merchant ? $transaction->merchant->name : __('translation.not_available') }}</span>
                    </div>
                    @if($transaction->terminal_id)
                    <div class="info-row">
                        <span class="info-label">{{ __('translation.terminal') }}:</span>
                        <span class="info-value">{{ $transaction->terminal_id }}</span>
                    </div>
                    @endif
                    <div class="info-row">
                        <span class="info-label">{{ __('translation.date') }}:</span>
                        <span class="info-value">{{ $transaction->created_at->format('Y-m-d') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">{{ __('translation.time') }}:</span>
                        <span class="info-value">{{ $transaction->created_at->format('H:i:s') }}</span>
                    </div>
                </div>
            </div>
            
            <div class="transaction-info">
                <div class="info-section">
                    <h3>{{ __('translation.transaction_information') }}</h3>
                    <div class="info-row">
                        <span class="info-label">{{ __('translation.transaction_id') }}:</span>
                        <span class="info-value">{{ $transaction->transaction_id }}</span>
                    </div>
                    @if($transaction->rrn)
                    <div class="info-row">
                        <span class="info-label">{{ __('translation.rrn') }}:</span>
                        <span class="info-value">{{ $transaction->rrn }}</span>
                    </div>
                    @endif
                    @if($transaction->auth_code)
                    <div class="info-row">
                        <span class="info-label">{{ __('translation.approval_code') }}:</span>
                        <span class="info-value">{{ $transaction->auth_code }}</span>
                    </div>
                    @endif
                    @if($transaction->batch_no)
                    <div class="info-row">
                        <span class="info-label">{{ __('translation.batch_no') }}:</span>
                        <span class="info-value">{{ $transaction->batch_no }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Amount Section -->
        <div class="amount-section">
            <div class="amount-label">{{ __('translation.transaction_amount') }}</div>
            <div class="amount-value">$ {{ number_format($transaction->amount, 2) }}</div>
        </div>

        <!-- Status Section -->
        <div class="status-section">
            <span class="status-badge status-{{ strtolower($transaction->status) }}">
                {{ $transaction->status }}
            </span>
        </div>

        <!-- Card Information -->
        @if($transaction->method || $transaction->card_number)
        <div class="card-info">
            <h4>{{ __('translation.card_information') }}</h4>
            <table class="transaction-table">
                @if($transaction->method)
                <tr>
                    <td><strong>{{ __('translation.payment_method') }}</strong></td>
                    <td>{{ ucfirst($transaction->method) }}</td>
                </tr>
                @endif
                @if($transaction->card_number)
                <tr>
                    <td><strong>{{ __('translation.card_number') }}</strong></td>
                    <td>**** **** **** {{ substr($transaction->card_number, -4) }}</td>
                </tr>
                @endif
                @if($transaction->expiry)
                <tr>
                    <td><strong>{{ __('translation.expiry') }}</strong></td>
                    <td>{{ $transaction->expiry }}</td>
                </tr>
                @endif
            </table>
        </div>
        @endif

        <!-- Additional Information -->
        <div class="info-section">
            <h3>{{ __('translation.additional_information') }}</h3>
            <table class="transaction-table">
                @if($transaction->mid)
                <tr>
                    <td><strong>{{ __('translation.mid') }}</strong></td>
                    <td>{{ $transaction->mid }}</td>
                </tr>
                @endif
                @if($transaction->tid)
                <tr>
                    <td><strong>{{ __('translation.tid') }}</strong></td>
                    <td>{{ $transaction->tid }}</td>
                </tr>
                @endif
                @if($transaction->atc)
                <tr>
                    <td><strong>{{ __('translation.atc') }}</strong></td>
                    <td>{{ $transaction->atc }}</td>
                </tr>
                @endif
                @if($transaction->sdk)
                <tr>
                    <td><strong>{{ __('translation.sdk_id') }}</strong></td>
                    <td>{{ $transaction->sdk }}</td>
                </tr>
                @endif
                @if($transaction->trace_no)
                <tr>
                    <td><strong>{{ __('translation.trace') }}</strong></td>
                    <td>{{ $transaction->trace_no }}</td>
                </tr>
                @endif
            </table>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>{{ __('translation.thank_you_for_your_business') }}</strong></p>
            <p>{{ __('translation.keep_this_receipt_for_your_records') }}</p>
            <p>{{ __('translation.if_you_have_questions_contact_support') }}</p>
            
            <div class="terms">
                <p><strong>{{ __('translation.terms_and_conditions') }}:</strong></p>
                <p>{{ __('translation.invoice_terms_text') }}</p>
            </div>
        </div>
    </div>
</body>
</html>
