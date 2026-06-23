@include('emails.partials.locale')
@php $fullName = trim(($firstName ?? '') . ' ' . ($lastName ?? '')); @endphp
<!DOCTYPE html>
<html lang="{{ $emailLocale }}" dir="{{ $emailDir }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('emails.partner_continuation_subject') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            direction: {{ $emailDir }};
            text-align: {{ $emailAlign }};
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .content {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .highlight {
            background-color: #e3f2fd;
            padding: 15px;
            {{ $emailBorderInline }}: 4px solid #2196f3;
            margin: 20px 0;
        }
        .button {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 4px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ __('emails.partner_continuation_heading') }}</h1>
        <p>{{ __('emails.partner_continuation_subheading') }}</p>
    </div>

    <div class="content">
        <h2>{{ __('emails.partner_continuation_greeting', ['name' => $fullName]) }}</h2>
        <p>{!! __('emails.partner_continuation_intro', ['business' => '<strong>'.e($businessName).'</strong>']) !!}</p>

        <div class="highlight">
            <h3>{{ __('emails.merchant_continuation_whats_next') }}</h3>
            <ul>
                <li>{{ __('emails.merchant_continuation_review') }}</li>
                <li>{{ __('emails.merchant_continuation_timeline') }}</li>
                <li>{{ __('emails.merchant_continuation_activation') }}</li>
                <li>{{ __('emails.merchant_continuation_login') }}</li>
            </ul>
        </div>

        <h3>{{ __('emails.merchant_continuation_can_do') }}</h3>
        <ul>
            <li>{{ __('emails.merchant_continuation_explore') }}</li>
            <li>{{ __('emails.merchant_continuation_guidelines') }}</li>
            <li>{{ __('emails.merchant_continuation_docs') }}</li>
            <li>{{ __('emails.merchant_continuation_support') }}</li>
        </ul>

        <div style="text-align: center;">
            <a href="{{ rtrim(config('app.frontend_url', config('app.url')), '/') }}/login" class="button" style="color: #fff;">
                {{ __('emails.merchant_continuation_access') }}
            </a>
        </div>

        <h3>{{ __('emails.merchant_continuation_need_help') }}</h3>
        @include('emails.partials.support-footer')
    </div>

    <div class="footer">
        <p>{{ __('emails.merchant_continuation_footer') }}</p>
        <p>{{ __('emails.merchant_continuation_regards') }}<br>{{ __('emails.merchant_continuation_team') }}</p>
        <p><small>{{ __('emails.merchant_continuation_sent_to', ['email' => $email]) }}</small></p>
    </div>
</body>
</html>
