@include('emails.partials.locale')
<!DOCTYPE html>
<html lang="{{ $emailLocale }}" dir="{{ $emailDir }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('emails.customer_rejection_subject') }}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; direction: {{ $emailDir }}; text-align: {{ $emailAlign }}; }
        .card { background: #fff; padding: 24px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .reason { background: #f8f9fa; padding: 16px; {{ $emailBorderInline }}: 4px solid #dc3545; margin: 16px 0; }
    </style>
</head>
<body>
    <div class="card">
        <h1>{{ __('emails.customer_rejection_title') }}</h1>
        <p>{{ __('emails.customer_rejection_greeting', ['name' => $customer->name]) }}</p>
        <p>{{ __('emails.customer_rejection_intro') }}</p>
        <div class="reason">
            <h3>{{ __('emails.customer_rejection_reason_title') }}</h3>
            <p>{{ $rejectionReason }}</p>
        </div>
        <p>{{ __('emails.customer_rejection_reapply') }}</p>
        <p>{{ __('emails.customer_rejection_thanks') }}</p>
        <p>{{ __('emails.customer_rejection_regards') }}<br>{{ config('app.name') }}</p>
        @include('emails.partials.support-footer')
    </div>
</body>
</html>
