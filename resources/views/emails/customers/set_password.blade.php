@include('emails.partials.locale')
<!DOCTYPE html>
<html lang="{{ $emailLocale }}" dir="{{ $emailDir }}">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>{{ __('emails.customer_set_password_subject') }}</title>
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
		.section-title {
			font-size: 16px;
			font-weight: 600;
			margin: 18px 0 8px;
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 8px;
		}
		.helper-text {
			font-size: 13px;
			color: #6b7280;
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
			🔐 {{ __('emails.customer_set_password_title') }}
		</h2>

		<p style="font-size:14px; margin-bottom:10px;">
			{{ __('emails.customer_set_password_greeting', ['name' => $customer->name ?: __('emails.label_name')]) }}
		</p>
		<p style="font-size:14px; margin-top:0; margin-bottom:16px;">
			{{ __('emails.customer_set_password_body') }}
		</p>

		<div class="inner-card">
			<div class="section-title" style="margin-top:0;">
				<span style="font-size:18px;">🔑</span>
				<span>{{ __('emails.customer_set_password_section') }}</span>
			</div>
			<p class="helper-text" style="margin-bottom:8px;">
				{{ __('emails.customer_set_password_click') }}
			</p>
			<a href="{{ $setupUrl }}" class="button" style="color:#ffffff;">{{ __('emails.customer_set_password_btn') }}</a>
			<p class="helper-text" style="margin-top:8px; margin-bottom:8px;">
				{{ __('emails.customer_set_password_expiry', ['hours' => $expiresInHours]) }}
			</p>
			<p class="helper-text" style="margin-bottom:4px;">{{ __('emails.customer_set_password_copy_url') }}</p>
			<p class="url-text">{{ $setupUrl }}</p>
		</div>

		<div class="footer">
			<p style="margin-bottom:4px;">{{ __('emails.customer_set_password_footer_help') }}</p>
			@include('emails.partials.support-footer')
			<p style="margin:4px 0 0; font-size:12px; color:#9ca3af;">{{ __('emails.customer_set_password_automated') }}</p>
		</div>
	</div>
</body>
</html>
