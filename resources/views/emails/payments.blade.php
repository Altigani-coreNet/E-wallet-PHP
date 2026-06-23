@include('emails.partials.locale')
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{{ $emailLocale }}" dir="{{ $emailDir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ __('emails.payment_subject', ['id' => $paymentLink->id]) }}</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8fafc; direction: {{ $emailDir }};">
    <!-- Wrapper Table -->
    <table cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #f8fafc; padding: 40px 0;">
        <tr>
            <td align="center">
                <!-- Main Container -->
                <table cellpadding="0" cellspacing="0" border="0" width="600" style="max-width: 600px; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); overflow: hidden;">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 30px; text-align: center;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td>
                                        <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 600; letter-spacing: -0.5px;">
                                            {{ __('emails.payment_platform') }}
                                        </h1>
                                        <p style="margin: 8px 0 0 0; color: #e2e8f0; font-size: 16px; opacity: 0.9;">
                                            {{ __('emails.payment_title') }}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Main Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            
                            <!-- Greeting -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td>
                                        <h2 style="margin: 0 0 20px 0; color: #1a202c; font-size: 24px; font-weight: 600;">
                                            {{ __('emails.payment_greeting_name', ['name' => $paymentLink->customer_name]) }}
                                        </h2>
                                        <p style="margin: 0 0 25px 0; color: #4a5568; font-size: 16px; line-height: 24px;">
                                            {{ __('emails.payment_invoice_ready') }}
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Invoice Details -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f7fafc; border-radius: 8px; margin-bottom: 30px;">
                                <tr>
                                    <td style="padding: 25px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="padding-bottom: 15px; border-bottom: 1px solid #e2e8f0;">
                                                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                                        <tr>
                                                            <td style="color: #4a5568; font-size: 14px; font-weight: 600;">{{ __('emails.payment_invoice_number') }}:</td>
                                                            <td align="right" style="color: #1a202c; font-size: 14px; font-weight: 600;">#{{ $paymentLink->id }}</td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding-top: 15px; padding-bottom: 15px; border-bottom: 1px solid #e2e8f0;">
                                                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                                        <tr>
                                                            <td style="color: #4a5568; font-size: 14px;">{{ __('emails.payment_issue_date') }}:</td>
                                                            <td align="right" style="color: #1a202c; font-size: 14px;">{{ $paymentLink->created_at->format('d-m-Y') }}</td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                            <tr>
                                                @if($paymentLink->scheduled_date)
                                                <td style="padding-top: 15px; padding-bottom: 15px; border-bottom: 1px solid #e2e8f0;">
                                                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                                        <tr>
                                                            <td style="color: #4a5568; font-size: 14px;">{{ __('emails.payment_due') }}:</td>
                                                            <td align="right" style="color: #e53e3e; font-size: 14px; font-weight: 600;">{{ Carbon\Carbon::parse($paymentLink->scheduled_date)->format('d-m-Y') }}</td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                @endif
                                            </tr>
                                            <tr>
                                                <td style="padding-top: 20px;">
                                                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                                        <tr>
                                                            <td style="color: #1a202c; font-size: 18px; font-weight: 700;">{{ __('emails.payment_total_amount') }}:</td>
                                                            <td align="right" style="color: #1a202c; font-size: 24px; font-weight: 700;">
                                                                @if($paymentLink->currency_symbol)
                                                                    {{ $paymentLink->currency_symbol }}{{ number_format($paymentLink->amount, 2) }} {{ $paymentLink->currency_code }}
                                                                @else
                                                                    {{ $paymentLink->amount }} {{ $paymentLink->currency_code ?? 'USD' }}
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Services/Items -->
                           

                            <!-- Payment Button -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 30px;">
                                <tr>
                                    <td align="center" style="padding: 20px 0;">
                                        <table cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="border-radius: 8px; background: linear-gradient(135deg, #48bb78 0%, #38a169 100%); box-shadow: 0 4px 12px rgba(72, 187, 120, 0.3);">
                                                    <a href="{{ $paymentLink->link }}" target="_blank" style="display: inline-block; padding: 16px 40px; color: #ffffff; text-decoration: none; font-size: 16px; font-weight: 600; border-radius: 8px; transition: all 0.3s ease;">
                                                        💳 @php
                                                            $payAmount = ($paymentLink->currency_symbol ?? '') . number_format($paymentLink->amount, 2) . ' ' . ($paymentLink->currency_code ?? 'USD');
                                                        @endphp
                                                        {{ __('emails.payment_pay_now_amount', ['amount' => $payAmount]) }}
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Payment Methods -->
                       

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #1a202c; padding: 30px;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center">
                                        <p style="margin: 0 0 10px 0; color: #e2e8f0; font-size: 14px; font-weight: 600;">
                                            {{ __('emails.payment_platform') }}
                                        </p>
                                        <p style="margin: 0 0 15px 0; color: #a0aec0; font-size: 12px; line-height: 18px;">
                                            {{ __('emails.payment_footer_address') }}<br>
                                            {{ __('emails.payment_footer_contact') }}
                                        </p>
                                        <table cellpadding="0" cellspacing="0" border="0" align="center">
                                            <tr>
                                                <td style="padding: 0 10px;">
                                                    <a href="https://fastpos.sd" style="color: #63b3ed; text-decoration: none; font-size: 12px;">{{ __('emails.payment_website') }}</a>
                                                </td>
                                                <td style="padding: 0 10px; color: #4a5568;">|</td>
                                                <td style="padding: 0 10px;">
                                                    <a href="mailto:support@corenetpay.com" style="color: #63b3ed; text-decoration: none; font-size: 12px;">{{ __('emails.payment_support_link') }}</a>
                                                </td>
                                                <td style="padding: 0 10px; color: #4a5568;">|</td>
                                                <td style="padding: 0 10px;">
                                                    <a href="https://fastpos.sd" style="color: #63b3ed; text-decoration: none; font-size: 12px;">{{ __('emails.payment_privacy') }}</a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

    <!-- Legal Footer -->
    <table cellpadding="0" cellspacing="0" border="0" width="100%">
        <tr>
            <td align="center" style="padding: 20px;">
                <p style="margin: 0; color: #a0aec0; font-size: 11px; line-height: 16px; max-width: 500px;">
                    {{ __('emails.payment_legal_footer', ['id' => $paymentLink->id]) }}
                </p>
            </td>
        </tr>
    </table>

</body>
</html>