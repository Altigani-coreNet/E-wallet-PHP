@include('emails.partials.locale')
<!DOCTYPE html>
<html lang="{{ $emailLocale }}" dir="{{ $emailDir }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('emails.merchant_approval_subject') }}</title>
    <style>
        body {
            margin: 0;
            padding: 40px 0;
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background: radial-gradient(circle at 20% 20%, #06056d 0, #04045a 35%, #020337 70%, #010120 100%);
        }
        .card {
            max-width: 640px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 28px 24px 24px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(15,23,42,0.35);
        }
        .section-title {
            font-size: 16px;
            font-weight: 600;
            margin: 18px 0 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .helper-text {
            font-size: 13px;
            color: #6b7280;
        }
        .status-pill {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 999px;
            background-color: #d1e7dd;
            color: #0f5132;
            font-weight: 600;
            font-size: 12px;
        }
        .details-card {
            background-color: #f8fafc;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            padding: 16px 18px;
            margin-top: 6px;
        }
        .label {
            font-size: 13px;
            color: #6b7280;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .value {
            font-size: 14px;
            color: #111827;
        }
        .button-primary {
            display: inline-block;
            padding: 12px 28px;
            background-color: #0d6efd;
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            margin: 8px 0 4px;
            font-weight: 600;
            font-size: 14px;
        }
        .footer {
            margin-top: 24px;
            padding-top: 14px;
            border-top: 1px solid #e5e7eb;
            color: #6c757d;
            font-size: 13px;
            text-align: center;
        }
    </style>
</head>
<body style="direction: {{ $emailDir }}; text-align: {{ $emailAlign }};">
    <div class="card">
        <h1 style="text-align:center; margin-top:0; font-size:20px; margin-bottom:8px;">
            🎉 {{ __('emails.merchant_approval_title') }}
        </h1>
        <p style="text-align:center; margin:0 0 18px; font-size:14px; color:#4b5563;">
            {{ __('emails.merchant_approval_intro', ['name' => $user->name, 'merchant' => $merchant->name]) }}
        </p>

        <div class="section-title">
            <span style="font-size:18px;">📊</span>
            <span>{{ __('emails.merchant_approval_status') }}</span>
        </div>
        <p class="helper-text" style="margin:0 0 6px;">
            {{ __('emails.merchant_approval_status_current') }}
        </p>
        <span class="status-pill">{{ __('emails.status_approved') }}</span>

        <div class="section-title" style="margin-top:20px;">
            <span style="font-size:18px;">🏢</span>
            <span>{{ __('emails.merchant_approval_details') }}</span>
        </div>
        <div class="details-card">
            <p class="label">{{ __('emails.merchant_approval_business_name') }}</p>
            <p class="value"><strong>{{ $merchant->name }}</strong></p>
            <p class="label" style="margin-top:10px;">{{ __('emails.merchant_approval_owner_email') }}</p>
            <p class="value">{{ $user->email }}</p>
        </div>

        <div class="section-title" style="margin-top:20px;">
            <span style="font-size:18px;">🚀</span>
            <span>{{ __('emails.merchant_approval_next_steps') }}</span>
        </div>
        <p class="helper-text" style="margin-bottom:8px;">
            {{ __('emails.merchant_approval_can_do') }}
        </p>
        <ul style="margin-top:0; {{ $emailPadInline }}:18px; font-size:14px; color:#4b5563;">
            <li>{{ __('emails.merchant_approval_step_1') }}</li>
            <li>{{ __('emails.merchant_approval_step_2') }}</li>
            <li>{{ __('emails.merchant_approval_step_3') }}</li>
        </ul>

        <div style="text-align:center; margin:20px 0 10px;">
            <a href="https://fastpos.sd/sales/dashboard" class="button-primary" style="color:#ffffff;">
                {{ __('emails.merchant_approval_btn') }}
            </a>
        </div>

        <p class="helper-text" style="text-align:center; margin-top:0;">
            {{ __('emails.merchant_approval_security') }}
        </p>

        <div class="section-title" style="margin-top:20px;">
            <span style="font-size:18px;">💡</span>
            <span>{{ __('emails.merchant_approval_assistance') }}</span>
        </div>
        <p class="helper-text" style="margin-bottom:4px;">
            {{ __('emails.merchant_approval_assistance_body') }}
        </p>
        @include('emails.partials.support-footer')

        <div class="footer">
            <p>{{ __('emails.merchant_approval_footer', ['app' => config('app.name')]) }}</p>
        </div>
    </div>
</body>
</html>


