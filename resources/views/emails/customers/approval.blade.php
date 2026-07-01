@include('emails.partials.locale')
<!DOCTYPE html>
<html lang="{{ $emailLocale }}" dir="{{ $emailDir }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('emails.customer_approval_subject') }}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; direction: {{ $emailDir }}; text-align: {{ $emailAlign }}; }
        .card { background: #fff; padding: 24px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
    </style>
</head>
<body>
    <div class="card">
        <h1>{{ __('emails.customer_approval_title') }}</h1>
        <p>{{ __('emails.customer_approval_greeting', ['name' => $customer->name]) }}</p>
        <p>{{ __('emails.customer_approval_intro') }}</p>
        <p>{{ __('emails.customer_approval_thanks') }}</p>
        <p>{{ __('emails.customer_approval_regards') }}<br>{{ config('app.name') }}</p>
        @include('emails.partials.support-footer')
    </div>
</body>
</html>
