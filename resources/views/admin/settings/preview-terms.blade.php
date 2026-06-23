<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('translation.contract_terms') }}</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600&display=swap" rel="stylesheet">
    
    <!-- Include DomPDF CSS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        .download-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #009ef7;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            z-index: 1000;
        }
        .download-btn:hover {
            background: #0095e8;
        }
        @media print {
            .download-btn {
                display: none;
            }
        }
        body {
            font-family: 'Cairo', sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 210mm; /* A4 width */
            margin: 0 auto;
            padding: 20mm; /* Standard margin for documents */
            background: #f5f5f5;
        }
        .document {
            background: #fff;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            min-height: 297mm; /* A4 height */
            position: relative;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #eee;
        }
        .header-logo {
            width: 25%;
        }
        .header-logo img {
            max-width: 150px;
            height: auto;
        }
        .header-title {
            width: 50%;
            text-align: center;
        }
        .header-title h1 {
            color: #1a1a1a;
            font-size: 24px;
            margin: 0;
            margin-bottom: 10px;
        }
        .header-title h2 {
            color: #666;
            font-size: 18px;
            margin: 0;
        }
        .header-system {
            width: 25%;
            text-align: right;
        }
        .header-system img {
            max-width: 150px;
            height: auto;
        }
        .merchant-details {
            width: 100%;
            margin-bottom: 3rem;
            border-collapse: collapse;
        }
        .merchant-details th,
        .merchant-details td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        .merchant-details th {
            font-weight: 600;
            background-color: #E1E3EA;
            width: 20%;
        }
        .merchant-details td {
            width: 30%;
        }
        .contract-date {
            text-align: right;
            margin-bottom: 2rem;
            font-size: 0.9em;
            color: #666;
        }
        .terms-content {
            font-size: 14px;
            line-height: 1.8;
            margin-top: 3rem;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .terms-content h1,
        .terms-content h2,
        .terms-content h3,
        .terms-content h4 {
            color: #1a1a1a;
            margin-top: 1.5rem;
            margin-bottom: 1rem;
        }
        .terms-content p {
            margin-bottom: 1rem;
        }
        .terms-content ul,
        .terms-content ol {
            margin-bottom: 1rem;
            padding-left: 2rem;
        }
        .terms-content li {
            margin-bottom: 0.5rem;
        }
        .terms-content strong {
            font-weight: 600;
            color: #1a1a1a;
        }
        .footer {
            position: absolute;
            bottom: 30px;
            left: 30px;
            right: 30px;
            text-align: center;
            font-size: 12px;
            color: #666;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        @media print {
            body {
                padding: 0;
                background: #fff;
            }
            .document {
                box-shadow: none;
            }
            .merchant-details th {
                background-color: #f8f9fa !important;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <button onclick="downloadPDF()" class="download-btn">
        <i class="fas fa-download"></i> {{ __('translation.download_pdf') }}
    </button>

    <div class="document" id="pdf-content">
        <div class="header">
            <div class="header-logo">
                <img src="{{ asset('logo.png') }}" alt="Company Logo">
            </div>
            <div class="header-title">
                <h1>{{ __('translation.merchant_agreement') }}</h1>
                <h2>{{ __('translation.contract_number') }}: {{ str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT) }}/{{ date('Y') }}</h2>
            </div>
            <div class="header-system">
                <img src="{{ asset('logo_light.jpg') }}" alt="System Logo">
            </div>
        </div>

        <div class="contract-date">
            {{ __('translation.date') }}: {{ now()->format('d/m/Y') }}
        </div>

        <table class="merchant-details">
            <tr>
                <th>{{ __('translation.merchant_name') }}</th>
                <td>{{ $merchant->name ?? $merchant->company_name }}</td>
                <th>{{ __('translation.merchant_id') }}</th>
                <td>{{ $merchant->merchant_code }}</td>
            </tr>
            <tr>
                <th>{{ __('translation.cr_number') }}</th>
                <td>{{ $merchant->cr_number }}</td>
                <th>{{ __('translation.trade_license') }}</th>
                <td>{{ $merchant->trade_license_number }}</td>
            </tr>
            <tr>
                <th>{{ __('translation.vat_number') }}</th>
                <td>{{ $merchant->vat_number }}</td>
                <th>{{ __('translation.country') }}</th>
                <td>{{ $merchant->country?->name }}</td>
            </tr>
            <tr>
                <th>{{ __('translation.city') }}</th>
                <td>{{ $merchant->city?->name }}</td>
                <th>{{ __('translation.address') }}</th>
                <td>{{ $merchant->address }}</td>
            </tr>
            <tr>
                <th>{{ __('translation.phone') }}</th>
                <td>{{ $merchant->phone }}</td>
                <th>{{ __('translation.email') }}</th>
                <td>{{ $merchant->email }}</td>
            </tr>
        </table>

        <h3 style="margin-top: 2rem; margin-bottom: 1rem; color: #1a1a1a;">{{ __('translation.service_information') }}</h3>
        <table class="merchant-details">
            <tr>
                <th>{{ __('translation.service_type') }}</th>
                <td>{{ __('translation.payment_solution') }}</td>
                <th>{{ __('translation.subscription_plan') }}</th>
                <td>{{ $merchant->subscription_plan ?? 'Standard' }}</td>
            </tr>
            <tr>
                <th>{{ __('translation.payment_methods') }}</th>
                <td>{{ implode(', ', \App\Models\Setting::getPaymentMethods()) }}</td>
                <th>{{ __('translation.transaction_fees') }}</th>
                <td>{{ \App\Models\Setting::getTransactionFee() }}% per transaction</td>
            </tr>
            <tr>
                <th>{{ __('translation.settlement_period') }}</th>
                <td>{{ \App\Models\Setting::getSettlementPeriod() }}</td>
                <th>{{ __('translation.contract_duration') }}</th>
                <td>{{ \App\Models\Setting::getContractDuration() }} Months</td>
            </tr>
            <tr>
                <th>{{ __('translation.start_date') }}</th>
                <td>{{ now()->format('d/m/Y') }}</td>
                <th>{{ __('translation.end_date') }}</th>
                <td>{{ now()->addYear()->format('d/m/Y') }}</td>
            </tr>
        </table>

        <div class="terms-content" style="margin-top: 2rem;">
            {!! $terms !!}
        </div>

        <div class="signatures" style="margin-top: 4rem; margin-bottom: 4rem;">
            <h3 style="margin-bottom: 1rem; color: #1a1a1a;">{{ __('translation.your_authorization') }}</h3>
            <p style="margin-bottom: 1rem;">{{ __('translation.terms_and_conditions_agreed') }}</p>
            <div style="display: flex; justify-content: space-between; gap: 4rem;">
                <div style="flex: 1;">
                    <div style="border: 1px solid #ddd; height: 120px; margin-bottom: 0.5rem;"></div>
                    <p style="font-weight: 600; color: #666; text-align: center;">{{ __('translation.customer_signature') }}</p>
                </div>
                <div style="flex: 1;">
                    <div style="border: 1px solid #ddd; height: 120px; margin-bottom: 0.5rem;"></div>
                    <p style="font-weight: 600; color: #666; text-align: center;">{{ __('translation.company_stamp') }}</p>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>{{ __('translation.document_footer') }}</p>
            <p>{{ __('translation.page') }} 1 {{ __('translation.of') }} 1</p>
        </div>
    </div>
    <script>
        function downloadPDF() {
            const element = document.getElementById('pdf-content');
            const options = {
                margin: [10, 10, 10, 10],
                filename: 'merchant_agreement.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            // Hide download button during PDF generation
            document.querySelector('.download-btn').style.display = 'none';
            
            html2pdf().set(options).from(element).save().then(() => {
                // Show download button after PDF generation
                document.querySelector('.download-btn').style.display = 'flex';
            });
        }
    </script>
</body>
</html>
