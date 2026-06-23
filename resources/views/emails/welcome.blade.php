@include('emails.partials.locale')
<!DOCTYPE html>
<html lang="{{ $emailLocale }}" dir="{{ $emailDir }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('emails.welcome_subject', ['merchant' => $merchant->name]) }}</title>
    <style>
        body {
            margin: 0;
            padding: 40px 0;
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background: radial-gradient(circle at 20% 20%, #06056d 0, #04045a 35%, #020337 70%, #010120 100%);
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(15,23,42,0.35);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #0d6efd;
        }
        .header h1 {
            color: #0d6efd;
            margin: 0;
            font-size: 28px;
        }
        .welcome-text {
            font-size: 18px;
            margin-bottom: 25px;
            text-align: center;
        }
        .credentials-box {
            background-color: #f8f9fa;
            border: 2px solid #0d6efd;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        .credentials-box h3 {
            color: #0d6efd;
            margin-top: 0;
            text-align: center;
        }
        .credential-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .credential-item:last-child {
            border-bottom: none;
        }
        .credential-label {
            font-weight: bold;
            color: #495057;
        }
        .credential-value {
            background-color: #e9ecef;
            padding: 5px 10px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 14px;
        }
        .important-note {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
        }
        .important-note h4 {
            color: #856404;
            margin-top: 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            color: #6c757d;
            font-size: 14px;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px;
            font-weight: bold;
            font-size: 16px;
        }
        .btn-primary {
            background-color: #0d6efd;
            color: #ffffff;
        }
        .btn-primary:hover {
            background-color: #0b5ed7;
        }
        .btn-success {
            background-color: #198754;
            color: #ffffff;
        }
        .btn-success:hover {
            background-color: #157347;
        }
    </style>
</head>
<body style="direction: {{ $emailDir }}; text-align: {{ $emailAlign }};">
    <div class="container">
        <div class="header">
            <h1>🎉 {{ __('emails.welcome_heading', ['merchant' => $merchant->name]) }}</h1>
        </div>

        <div class="welcome-text">
            <p>{{ __('emails.welcome_greeting', ['name' => $user->name]) }}</p>
            <p>{{ __('emails.welcome_body', ['merchant' => $merchant->name]) }}</p>
        </div>

        <div class="credentials-box">
            <h3>🔐 {{ __('emails.welcome_credentials_title') }}</h3>
            <div class="credential-item">
                <span class="credential-label">{{ __('emails.label_email') }}:</span>
                <span class="credential-value">{{ $user->email }}</span>
            </div>
            <div class="credential-item">
                <span class="credential-label">{{ __('emails.label_password') }}:</span>
                <span class="credential-value">{{ $password }}</span>
            </div>
        </div>

        <div class="important-note">
            <h4>⚠️ {{ __('emails.welcome_security_title') }}</h4>
            <p>{{ __('emails.welcome_security_body') }}</p>
        </div>

        <div class="button-container">
            <a href="https://fastpos.sd/merchant/register" class="btn btn-success" style="color:#ffffff;">
                🚀 {{ __('emails.btn_complete_registration') }}
            </a>
            <a href="https://fastpos.sd/login" class="btn btn-primary" style="color:#ffffff;">
                🔐 {{ __('emails.btn_login') }}
            </a>
        </div>

        <div class="footer">
            @include('emails.partials.support-footer')
            <p style="margin-top:14px; margin-bottom:0;">{{ __('emails.welcome_thanks', ['merchant' => $merchant->name]) }}</p>
        </div>
    </div>
</body>
</html>

