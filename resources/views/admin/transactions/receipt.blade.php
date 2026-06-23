<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('translation.transaction_receipt') }} - {{ $transaction->transaction_id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
            background: #fff;
            padding: 20px;
        }
        
        .receipt-container {
            max-width: 400px;
            margin: 0 auto;
            border: 1px solid #ddd;
            padding: 20px;
            background: #fff;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .company-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .company-address {
            font-size: 10px;
            margin-bottom: 5px;
        }
        
        .receipt-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .transaction-info {
            margin-bottom: 20px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            border-bottom: 1px dotted #ccc;
            padding-bottom: 5px;
        }
        
        .info-label {
            font-weight: bold;
            min-width: 120px;
        }
        
        .info-value {
            text-align: right;
            flex: 1;
        }
        
        .amount-section {
            text-align: center;
            border: 2px solid #000;
            padding: 15px;
            margin: 20px 0;
            background: #f9f9f9;
        }
        
        .amount-label {
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .amount-value {
            font-size: 24px;
            font-weight: bold;
        }
        
        .card-info {
            background: #f5f5f5;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        
        .card-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 10px;
        }
        
        .status-approved {
            color: #28a745;
            font-weight: bold;
        }
        
        .status-declined {
            color: #dc3545;
            font-weight: bold;
        }
        
        .status-pending {
            color: #ffc107;
            font-weight: bold;
        }
        
        .qr-code {
            text-align: center;
            margin: 20px 0;
        }
        
        .qr-code img {
            width: 100px;
            height: 100px;
        }
        
        @media print {
            body {
                padding: 0;
            }
            
            .receipt-container {
                border: none;
                max-width: none;
            }
            
            .no-print {
                display: none;
            }
        }
        
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .print-button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">
        🖨️ {{ __('translation.print_receipt') }}
    </button>
    
    <div class="receipt-container">
        <!-- Header -->
        <div class="header">
            <div class="company-name">CORENET TECH POS SYSTEM</div>
            <div class="company-address">Corenet Tech Headquarters, Tech District, City</div>
            <div class="company-address">Tel: +1-555-CORENET | Email: info@corenettech.com</div>
            <div class="receipt-title">{{ __('translation.transaction_receipt') }}</div>
        </div>
        
        <!-- Transaction Information -->
        <div class="transaction-info">
            <div class="info-row">
                <span class="info-label">{{ __('translation.transaction_id') }}:</span>
                <span class="info-value">{{ $transaction->transaction_id }}</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">{{ __('translation.date_time') }}:</span>
                <span class="info-value">{{ $transaction->created_at->format('Y-m-d H:i:s') }}</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">{{ __('translation.transaction_type') }}:</span>
                <span class="info-value">{{ $transaction->transaction_type }}</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">{{ __('translation.status') }}:</span>
                <span class="info-value status-{{ strtolower($transaction->status) }}">
                    {{ $transaction->status }}
                    @if($transaction->status === 'APPROVED')
                        (00)
                    @endif
                </span>
            </div>
            
            @if($transaction->rrn)
            <div class="info-row">
                <span class="info-label">{{ __('translation.rrn') }}:</span>
                <span class="info-value">{{ $transaction->rrn }}</span>
            </div>
            @endif
            
            @if($transaction->batch_no)
            <div class="info-row">
                <span class="info-label">{{ __('translation.batch_no') }}:</span>
                <span class="info-value">{{ $transaction->batch_no }}</span>
            </div>
            @endif
            
            @if($transaction->trace_no)
            <div class="info-row">
                <span class="info-label">{{ __('translation.trace') }}:</span>
                <span class="info-value">{{ $transaction->trace_no }}</span>
            </div>
            @endif
            
            @if($transaction->auth_code)
            <div class="info-row">
                <span class="info-label">{{ __('translation.approval_code') }}:</span>
                <span class="info-value">{{ $transaction->auth_code }}</span>
            </div>
            @endif
            
            @if($transaction->invoice_no)
            <div class="info-row">
                <span class="info-label">{{ __('translation.invoice_no') }}:</span>
                <span class="info-value">{{ $transaction->invoice_no }}</span>
            </div>
            @endif
        </div>
        
        <!-- Amount Section -->
        <div class="amount-section">
            <div class="amount-label">{{ __('translation.amount') }}</div>
            <div class="amount-value">$ {{ number_format($transaction->amount, 2) }}</div>
        </div>
        
        <!-- Card Information -->
        <div class="card-info">
            <div class="card-row">
                <span class="info-label">{{ __('translation.card_type') }}:</span>
                <span class="info-value">{{ $transaction->method ?: 'N/A' }}</span>
            </div>
            
            @if($transaction->card_number)
            <div class="card-row">
                <span class="info-label">{{ __('translation.card_number') }}:</span>
                <span class="info-value">{{ $transaction->card_number }}</span>
            </div>
            @endif
            
            @if($transaction->expiry)
            <div class="card-row">
                <span class="info-label">{{ __('translation.expiry') }}:</span>
                <span class="info-value">{{ $transaction->expiry }}</span>
            </div>
            @endif
            
            @if($transaction->paymentMethod && $transaction->paymentMethod->cardholder_name)
            <div class="card-row">
                <span class="info-label">{{ __('translation.cardholder') }}:</span>
                <span class="info-value">{{ $transaction->paymentMethod->cardholder_name }}</span>
            </div>
            @endif
            
            @if($transaction->paymentMethod && $transaction->paymentMethod->entry_mode)
            <div class="card-row">
                <span class="info-label">{{ __('translation.entry_mode') }}:</span>
                <span class="info-value">{{ $transaction->paymentMethod->entry_mode }}</span>
            </div>
            @endif
        </div>
        
        <!-- Terminal Information -->
        <div class="transaction-info">
            @if($transaction->terminal_id)
            <div class="info-row">
                <span class="info-label">{{ __('translation.terminal') }}:</span>
                <span class="info-value">{{ $transaction->terminal_id }}</span>
            </div>
            @endif
            
            @if($transaction->merchant)
            <div class="info-row">
                <span class="info-label">{{ __('translation.merchant') }}:</span>
                <span class="info-value">{{ $transaction->merchant->name }}</span>
            </div>
            @endif
            
            @if($transaction->sdk)
            <div class="info-row">
                <span class="info-label">{{ __('translation.sdk_id') }}:</span>
                <span class="info-value">{{ $transaction->sdk }}</span>
            </div>
            @endif
            
            @if($transaction->mid)
            <div class="info-row">
                <span class="info-label">{{ __('translation.mid') }}:</span>
                <span class="info-value">{{ $transaction->mid }}</span>
            </div>
            @endif
            
            @if($transaction->tid)
            <div class="info-row">
                <span class="info-label">{{ __('translation.tid') }}:</span>
                <span class="info-value">{{ $transaction->tid }}</span>
            </div>
            @endif
        </div>
        
        <!-- QR Code Placeholder -->
        <div class="qr-code">
            <div style="width: 100px; height: 100px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; margin: 0 auto; border: 1px solid #ddd;">
                <span style="font-size: 10px; color: #666;">QR Code</span>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div>{{ __('translation.thank_you_for_your_business') }}</div>
            <div>{{ __('translation.keep_this_receipt_for_your_records') }}</div>
            <div style="margin-top: 10px;">{{ __('translation.receipt_generated_on') }}: {{ now()->format('Y-m-d H:i:s') }}</div>
        </div>
    </div>
    
    <script>
        // Auto-print functionality (optional)
        // window.onload = function() {
        //     window.print();
        // };
        
        // Add any additional JavaScript functionality here
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
        });
    </script>
</body>
</html>
