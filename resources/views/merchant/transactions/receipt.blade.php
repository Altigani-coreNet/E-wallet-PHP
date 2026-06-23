<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('translation.transaction_receipt') }} - {{ $transaction->transaction_id }}</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }
        .receipt-container {
            max-width: 400px;
            margin: 20px auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .receipt-header {
            text-align: center;
            border-bottom: 2px dashed #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .logo-section {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }
        .logo-box {
            width: 40px;
            height: 30px;
            background-color: #333;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 16px;
            margin-right: 10px;
        }
        .fastpay-text {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        .receipt-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        .receipt-subtitle {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        .merchant-address {
            font-size: 12px;
            color: #666;
            margin-bottom: 10px;
        }
        .receipt-section {
            margin-bottom: 15px;
        }
        .receipt-label {
            font-weight: bold;
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }
        .receipt-value {
            font-size: 16px;
            margin-bottom: 8px;
        }
        .receipt-amount {
            font-size: 28px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .receipt-status {
            text-align: center;
            padding: 10px;
            border-radius: 5px;
            margin: 15px 0;
            font-weight: bold;
            font-size: 16px;
        }
        .qr-section {
            text-align: center;
            margin: 20px 0;
        }
        .qr-text {
            font-size: 14px;
            margin-bottom: 10px;
            font-weight: bold;
        }
        .qr-code-container {
            display: inline-block;
            margin: 10px 0;
            padding: 10px;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .qr-code-container canvas {
            width: 120px !important;
            height: 120px !important;
            display: block;
            border-radius: 4px;
            margin: 0 auto;
        }
        .qr-info {
            margin-top: 8px;
            color: #666;
        }
        .payment-details {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .detail-label {
            font-weight: bold;
            color: #333;
        }
        .detail-value {
            color: #666;
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
        .receipt-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
        }
        .card-info {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .print-button, .download-button {
            position: fixed;
            top: 20px;
            z-index: 1000;
        }
        .print-button {
            right: 20px;
        }
        .download-button {
            right: 120px;
        }
        @media print {
            .print-button, .download-button {
                display: none;
            }
            body {
                background-color: white;
            }
            .receipt-container {
                box-shadow: none;
                margin: 0;
                max-width: none;
            }
        }
    </style>
</head>
<body>
    <button class="btn btn-success download-button" onclick="downloadPDF(this)">
        <i class="fas fa-download"></i> Download PDF
    </button>
    
    <button class="btn btn-primary print-button" onclick="window.print()">
        <i class="fas fa-print"></i> {{ __('translation.print') }}
    </button>

    <div class="receipt-container">
        <!-- Receipt Header -->
        <div class="receipt-header">
            {{-- <div class="logo-section">
                <div class="logo-box">FP</div>
                <div class="fastpay-text">fastpay</div>
            </div> --}}
            <div class="receipt-title">{{ $transaction->merchant ? $transaction->merchant->name : __('translation.merchant') }}</div>
            @if($transaction->merchant && $transaction->merchant->address)
            <div class="merchant-address">{{ $transaction->merchant->address }}</div>
            @endif
        </div>

        <!-- Status -->
        <div class="receipt-status status-{{ strtolower($transaction->status) }}">
            @if($transaction->status == 'approved' || $transaction->status == 'success')
                Captured Successfully
            @else
                {{ ucfirst($transaction->status) }}
            @endif
        </div>

        <!-- Amount -->
        <div class="receipt-amount">
            Amount: $ {{ number_format($transaction->amount, 2) }}
        </div>

        <!-- QR Code Section -->
        <div class="qr-section">
            <div class="qr-text">Scan QR For E-Receipt</div>
            <div class="qr-code-container">
                <!-- QR code will be generated here by kjua -->
            </div>
            <div class="qr-info">
                <small>Transaction ID: {{ substr($transaction->transaction_id, -6) }}</small>
            </div>
        </div>

        <!-- Payment Details -->
        <div class="payment-details">
            <div class="detail-row">
                <span class="detail-label">Date:</span>
                <span class="detail-value">{{ $transaction->created_at->format('m/d/Y') }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Time:</span>
                <span class="detail-value">{{ $transaction->created_at->format('H:i:s') }}</span>
            </div>
            @if($transaction->merchant)
            <div class="detail-row">
                <span class="detail-label">Merchant ID:</span>
                <span class="detail-value">{{ $transaction->merchant->merchant_id ?? 'MERCH' . strtoupper(substr(md5($transaction->merchant->id), 0, 8)) }}</span>
            </div>
            @endif
            @if($transaction->terminal_id)
            <div class="detail-row">
                <span class="detail-label">Terminal ID:</span>
                <span class="detail-value">{{ $transaction->terminal_id }}</span>
            </div>
            @endif
            @if($transaction->card_number)
            <div class="detail-row">
                <span class="detail-label">Card Number:</span>
                <span class="detail-value">****{{ substr($transaction->card_number, -4) }}</span>
            </div>
            @endif
            @if($transaction->expiry)
            <div class="detail-row">
                <span class="detail-label">Expiry:</span>
                <span class="detail-value">{{ $transaction->expiry }}</span>
            </div>
            @endif
            <div class="detail-row">
                <span class="detail-label">Payment Type:</span>
                <span class="detail-value">{{ ucfirst($transaction->method ?? 'POS') }}</span>
            </div>
            @if($transaction->rrn)
            <div class="detail-row">
                <span class="detail-label">Ref. No:</span>
                <span class="detail-value">{{ $transaction->rrn }}</span>
            </div>
            @endif
            <div class="detail-row">
                <span class="detail-label">Response Message:</span>
                <span class="detail-value">{{ strtolower($transaction->status) }}</span>
            </div>
        </div>

        <!-- Receipt Footer -->
        <div class="receipt-footer">
        Powered by Corenet Technology
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Font Awesome -->
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
    <!-- QR Code Library - kjua -->
    <script src="https://cdn.jsdelivr.net/npm/kjua@0.9.0/dist/kjua.min.js"></script>
    <!-- HTML2PDF Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    
    <script>
        function generateQRCode() {
            // Check if kjua library is loaded
            if (typeof kjua === 'undefined') {
                console.error('kjua library not loaded');
                showQRError('QR Code library failed to load');
                return;
            }

            // QR Code data from the server
            const qrData = {!! json_encode($qrData) !!};
            
            // Get the container element
            const container = document.querySelector('.qr-code-container');
            
            if (!container) {
                console.error('QR code container not found');
                return;
            }

            try {
                // Generate QR code using kjua
                const qr = kjua({
                    render: 'canvas',
                    crisp: true,
                    minVersion: 1,
                    ecLevel: 'M',
                    size: 120,
                    ratio: null,
                    fill: '#000000',
                    back: '#FFFFFF',
                    text: qrData,
                    rounded: 0,
                    quiet: 1,
                    mode: 'plain',
                    mSize: 0.1,
                    mPosX: 0.5,
                    mPosY: 0.5,
                    label: '',
                    fontname: 'sans',
                    fontcolor: '#000000',
                    image: null
                });

                // Clear the container and add the QR code
                container.innerHTML = '';
                container.appendChild(qr);
                
                console.log('QR Code generated successfully with kjua');
            } catch (error) {
                console.error('QR Code generation failed:', error);
                showQRError('QR Code generation failed');
            }
        }

        function showQRError(message) {
            const container = document.querySelector('.qr-code-container');
            if (container) {
                container.innerHTML = `
                    <div class="qr-error" style="color: #666; font-size: 12px; text-align: center; padding: 20px; border: 1px dashed #ccc; background: #f9f9f9; border-radius: 8px;">
                        ${message}
                    </div>
                `;
            }
        }

        // Wait for both DOM and kjua library to be ready
        document.addEventListener('DOMContentLoaded', function() {
            // Wait a bit for the kjua library to load
            setTimeout(function() {
                generateQRCode();
            }, 500);
        });
        
        // Also try when window loads (fallback)
        window.addEventListener('load', function() {
            setTimeout(function() {
                if (typeof kjua !== 'undefined') {
                    generateQRCode();
                }
            }, 100);
        });

        // PDF Download Function
        function downloadPDF(buttonElement) {
            console.log('Starting PDF download...');
            
            // Show loading state
            const originalText = buttonElement.innerHTML;
            buttonElement.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Downloading...';
            buttonElement.disabled = true;
            
            // Define the content to be downloaded - target the receipt container
            const element = document.querySelector('.receipt-container');
            
            console.log('Target element found:', element);
            console.log('Element content length:', element ? element.innerHTML.length : 'Element not found');
            
            if (!element) {
                console.error('Target element receipt-container not found!');
                // Reset button state
                buttonElement.innerHTML = originalText;
                buttonElement.disabled = false;
                alert('Error: Receipt container not found');
                return;
            }
            
            // HTML2PDF options
            const opt = {
                margin: [10, 10, 10, 10],
                filename: 'transaction-receipt-' + '{{ $transaction->transaction_id }}' + '.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { 
                    scale: 2,
                    useCORS: true,
                    letterRendering: true,
                    allowTaint: true,
                    logging: true
                },
                jsPDF: { 
                    unit: 'mm', 
                    format: 'a4', 
                    orientation: 'portrait',
                    compress: true
                },
                pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
            };
            
            console.log('Starting html2pdf generation...');
            
            // Generate PDF
            html2pdf().set(opt).from(element).save().then(function() {
                console.log('PDF downloaded successfully');
                // Reset button state
                buttonElement.innerHTML = originalText;
                buttonElement.disabled = false;
                
                // Show success message
                alert('PDF downloaded successfully!');
            }).catch(function(error) {
                console.error('PDF download error:', error);
                // Reset button state
                buttonElement.innerHTML = originalText;
                buttonElement.disabled = false;
                
                // Show error message
                alert('Error downloading PDF: ' + error.message);
            });
        }
    </script>
</body>
</html>
