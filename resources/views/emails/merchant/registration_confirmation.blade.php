@include('emails.partials.locale')
<!DOCTYPE html>
<html lang="{{ $emailLocale }}" dir="{{ $emailDir }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('emails.merchant_registration_confirmation_subject', ['app' => config('app.name')]) }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
            direction: {{ $emailDir }};
            text-align: {{ $emailAlign }};
        }
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #17a2b8;
        }
        .header h1 { color: #17a2b8; margin: 0; font-size: 28px; }
        .status-box {
            background-color: #f8f9fa;
            border: 2px solid #17a2b8;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        .status-box h3 { color: #17a2b8; margin-top: 0; text-align: center; }
        .business-details {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .important-note {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
        }
        .important-note h4 { color: #856404; margin-top: 0; }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            color: #6c757d;
            font-size: 14px;
        }
        .steps { margin: 20px 0; {{ $emailPadInline }}: 20px; }
        .steps li { margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎯 {{ __('emails.merchant_registration_confirmation_heading') }}</h1>
        </div>

        <div class="content">
            <p>{{ __('emails.merchant_registration_confirmation_dear', ['name' => $user->name]) }}</p>
            <p>{{ __('emails.merchant_registration_confirmation_received', ['app' => config('app.name'), 'business' => $merchant->name]) }}</p>
        </div>

        <div class="status-box">
            <h3>📋 {{ __('emails.merchant_registration_confirmation_status_title') }}</h3>
            <p style="text-align: center; font-size: 18px;">
                {{ __('emails.merchant_registration_confirmation_under_review') }}
            </p>
            <div class="business-details">
                <p><strong>{{ __('emails.merchant_status_business_name') }}:</strong> {{ $merchant->name }}</p>
                <p><strong>{{ __('emails.merchant_registration_confirmation_reg_email') }}:</strong> {{ $user->email }}</p>
                <p><strong>{{ __('emails.merchant_registration_confirmation_business_type') }}:</strong> {{ $merchant->business_type }}</p>
            </div>
        </div>

        <div class="important-note">
            <h4>⏳ {{ __('emails.merchant_registration_confirmation_next_steps_title') }}</h4>
            <ol class="steps">
                <li>{{ __('emails.merchant_registration_confirmation_step_1') }}</li>
                <li>{{ __('emails.merchant_registration_confirmation_step_2') }}</li>
                <li>{{ __('emails.merchant_registration_confirmation_step_3') }}</li>
            </ol>
        </div>

        <div class="important-note" style="background-color: #e8f5e9; border-color: #c8e6c9;">
            <h4>💡 {{ __('emails.merchant_registration_confirmation_expect_title') }}</h4>
            <ul class="steps">
                <li>{{ __('emails.merchant_registration_confirmation_expect_1') }}</li>
                <li>{{ __('emails.merchant_registration_confirmation_expect_2') }}</li>
                <li>{{ __('emails.merchant_registration_confirmation_expect_3') }}</li>
                <li>{{ __('emails.merchant_registration_confirmation_expect_4') }}</li>
            </ul>
        </div>

        <div class="footer">
            <p>{{ __('emails.merchant_registration_confirmation_questions') }}</p>
            <p>{{ __('emails.merchant_registration_confirmation_thanks', ['app' => config('app.name')]) }}</p>
            <p><small>{{ __('emails.merchant_registration_confirmation_automated') }}</small></p>
        </div>
    </div>
</body>
</html>
