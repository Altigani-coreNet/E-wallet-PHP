@include('emails.partials.locale')
<!DOCTYPE html>
<html lang="{{ $emailLocale }}" dir="{{ $emailDir }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('emails.password_reset_success_subject') }}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #f8f9fa; padding: 20px; text-align: center; border-radius: 5px; margin-bottom: 20px; }
        .content { background-color: #ffffff; padding: 20px; border: 1px solid #dee2e6; border-radius: 5px; margin-bottom: 20px; }
        .footer { text-align: center; color: #6c757d; font-size: 14px; margin-top: 20px; }
    </style>
    </head>
<body style="direction: {{ $emailDir }}; text-align: {{ $emailAlign }};">
    <div class="header">
        <h2>{{ __('emails.password_reset_success_subject') }}</h2>
    </div>

    <div class="content">
        <p>{{ __('emails.password_reset_success_greeting', ['name' => $user->name]) }}</p>
        <p>{{ __('emails.password_reset_success_body') }}</p>
    </div>

    <div class="footer">
        <p>{{ __('emails.footer_rights', ['year' => date('Y'), 'app' => config('app.name')]) }}</p>
    </div>
</body>
</html>


