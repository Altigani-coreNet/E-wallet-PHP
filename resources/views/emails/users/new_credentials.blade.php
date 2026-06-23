@include('emails.partials.locale')
<!DOCTYPE html>
<html lang="{{ $emailLocale }}" dir="{{ $emailDir }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('emails.new_credentials_subject') }}</title>
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
            border-bottom: 2px solid #007bff;
        }
        .header h1 { color: #007bff; margin: 0; font-size: 28px; }
        .welcome-text { font-size: 18px; margin-bottom: 25px; text-align: center; }
        .credentials-box {
            background-color: #f8f9fa;
            border: 2px solid #007bff;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        .credentials-box h3 { color: #007bff; margin-top: 0; text-align: center; }
        .credential-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
            gap: 12px;
        }
        .credential-item:last-child { border-bottom: none; }
        .credential-label { font-weight: bold; color: #495057; }
        .credential-value {
            background-color: #e9ecef;
            padding: 5px 10px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 14px;
            direction: ltr;
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
        .login-button {
            display: inline-block;
            background-color: #007bff;
            color: #ffffff;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 {{ __('emails.new_credentials_title') }}</h1>
        </div>

        <div class="welcome-text">
            <p>{{ __('emails.new_credentials_greeting', ['name' => $user->name]) }}</p>
            <p>{{ __('emails.new_credentials_body') }}</p>
        </div>

        <div class="credentials-box">
            <h3>📧 {{ __('emails.new_credentials_account_info') }}</h3>
            <div class="credential-item">
                <span class="credential-label">{{ __('emails.new_credentials_full_name') }}:</span>
                <span class="credential-value">{{ $user->name }}</span>
            </div>
            <div class="credential-item">
                <span class="credential-label">{{ __('emails.new_credentials_email_address') }}:</span>
                <span class="credential-value">{{ $user->email }}</span>
            </div>
            <div class="credential-item">
                <span class="credential-label">{{ __('emails.label_password') }}:</span>
                <span class="credential-value">{{ $plainPassword }}</span>
            </div>
        </div>

        <div class="important-note">
            <h4>⚠️ {{ __('emails.welcome_security_title') }}</h4>
            <p>{{ __('emails.new_credentials_security') }}</p>
        </div>

        <div style="text-align: center;">
            <a href="{{ rtrim(config('app.frontend_url', config('app.url')), '/') }}/login" class="login-button">
                {{ __('emails.new_credentials_login_btn') }}
            </a>
        </div>

        <div class="footer">
            <p>{{ __('emails.new_credentials_footer_questions') }}</p>
            <p>{{ __('emails.new_credentials_footer_thanks', ['app' => config('app.name')]) }}</p>
            <p><small>{{ __('emails.new_credentials_automated') }}</small></p>
        </div>
    </div>
</body>
</html>
