@include('emails.partials.locale')
<!DOCTYPE html>
<html lang="{{ $emailLocale }}" dir="{{ $emailDir }}">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>{{ __('emails.customer_email_verification_subject') }}</title>
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
			max-width: 600px;
			margin: 0 auto;
			background-color: #ffffff;
			padding: 24px 20px 20px;
			border: 1px solid #dee2e6;
			border-radius: 10px;
			box-shadow: 0 0 20px rgba(15,23,42,0.35);
		}
		.button {
			display: inline-block;
			padding: 12px 28px;
			background-color: #0d6efd;
			color: #ffffff;
			text-decoration: none;
			border-radius: 6px;
			margin: 12px 0 4px;
			font-weight: 600;
			font-size: 14px;
		}
		.inner-card {
			background-color: #f8fafc;
			border-radius: 10px;
			border: 1px solid #e2e8f0;
			padding: 16px 18px;
			text-align: center;
		}
		.footer {
			margin-top: 24px;
			text-align: left;
			color: #6b7280;
			font-size: 13px;
		}
		.url-text {
			word-break: break-all;
			font-size: 12px;
			color: #4b5563;
		}
	</style>
</head>
<body style="direction: {{ $emailDir }}; text-align: {{ $emailAlign }};">
	<div class="card">
		<h2 style="margin-top:0; font-size:18px;">
			✉️ {{ __('emails.customer_email_verification_title') }}
		</h2>

		<p style="font-size:14px; margin-bottom:10px;">
			{{ __('emails.customer_email_verification_greeting', ['name' => $customer->name ?: __('emails.label_name')]) }}
		</p>
		<p style="font-size:14px; margin-top:0; margin-bottom:16px;">
			{{ __('emails.customer_email_verification_body') }}
		</p>

		<div class="inner-card">
			<p style="margin:0 0 8px; font-size:14px; font-weight:600;">
				{{ __('emails.customer_email_verification_section') }}
			</p>
			<a href="{{ $verifyUrl }}" class="button" target="_blank" rel="noopener noreferrer">
				{{ __('emails.customer_email_verification_btn') }}
			</a>
			<p style="margin:12px 0 0; font-size:12px; color:#6b7280;">
				{{ __('emails.customer_email_verification_expiry', ['hours' => $expiresInHours]) }}
			</p>
		</div>

		<p style="font-size:12px; margin-top:16px; color:#6b7280;">
			{{ __('emails.customer_email_verification_copy_url') }}
		</p>
		<p class="url-text">{{ $verifyUrl }}</p>

		<div class="footer">
			<p style="margin:0;">{{ __('emails.customer_email_verification_footer_help') }}</p>
			<p style="margin:8px 0 0;">{{ __('emails.customer_email_verification_automated') }}</p>
		</div>
	</div>
</body>
</html>
