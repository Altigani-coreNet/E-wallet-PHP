@include('emails.partials.locale')
<!DOCTYPE html>
<html lang="{{ $emailLocale }}" dir="{{ $emailDir }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('emails.verification_code_subject') }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 20px; direction: {{ $emailDir }}; text-align: {{ $emailAlign }};">
    <div style="max-width: 600px; margin: 0 auto; background: #fff; padding: 20px;">
        <h2 style="color: #333; text-align: center;">{{ __('emails.verification_code_title') }}</h2>

        <div style="text-align: center; padding: 20px; background: #f8f9fa; margin: 20px 0;">
            <h1 style="font-size: 36px; margin: 0; color: #007bff; letter-spacing: 5px; direction: ltr;">{{ $code }}</h1>
        </div>

        <p style="color: #666; text-align: center;">
            {{ __('emails.verification_code_body') }}
        </p>

        <p style="color: #666; text-align: center; font-size: 12px; margin-top: 30px;">
            {{ __('emails.verification_code_ignore') }}
        </p>
    </div>
</body>
</html>