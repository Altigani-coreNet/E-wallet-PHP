@include('emails.partials.locale')
<!DOCTYPE html>
<html lang="{{ $emailLocale }}" dir="{{ $emailDir }}">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>{{ __('emails.account_created_subject') }}</title>
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
			margin-bottom: 20px;
			padding-bottom: 14px;
			border-bottom: 2px solid #198754;
		}
		.header h1 {
			color: #198754;
			margin: 0;
			font-size: 20px;
		}
		.subtitle {
			margin: 0;
			margin-top: 6px;
			font-size: 14px;
			color: #4b5563;
		}
		.section-title {
			font-size: 16px;
			font-weight: 600;
			margin: 0 0 8px;
			display: flex;
			align-items: center;
			gap: 8px;
		}
		.helper-text {
			font-size: 13px;
			color: #6b7280;
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
		.footer {
			text-align: center;
			margin-top: 30px;
			padding-top: 20px;
			border-top: 1px solid #dee2e6;
			color: #6c757d;
			font-size: 14px;
		}
		.account-details {
			background-color: #f8fafc;
			border-radius: 8px;
			border: 1px solid #e2e8f0;
			padding: 16px 18px;
			margin: 18px 0 22px;
		}
		.detail-row {
			font-size: 14px;
			color: #111827;
			margin: 4px 0;
		}
	</style>
</head>
<body style="direction: {{ $emailDir }}; text-align: {{ $emailAlign }};">
	<div class="container">
		<div class="header">
			<h1>{{ __('emails.account_created_heading') }} 🎉</h1>
			<p class="subtitle">
				{{ __('emails.account_created_subtitle') }}
			</p>
		</div>

		<p style="font-size:14px; margin-bottom:14px;">
			{!! __('emails.account_created_greeting', ['name' => '<strong>'.e(strtoupper($user->name)).'</strong>']) !!}
		</p>

		<div class="section-title" style="margin-top:10px;">
			<span style="font-size:18px;">🧾</span>
			<span>{{ __('emails.account_details') }}</span>
		</div>
		<div class="account-details">
			<div class="detail-row">
				<strong>{{ __('emails.label_name') }}:</strong> {{ $user->name }}{{ $user->last_name ? ' ' . $user->last_name : '' }}
			</div>
			<div class="detail-row">
				<strong>{{ __('emails.label_email') }}:</strong> {{ $user->email }}
			</div>
			@if(!empty($userName))
			<div class="detail-row">
				<strong>{{ __('emails.label_username') }}:</strong> {{ $userName }}
			</div>
			@endif
			@if(!empty($user->phone))
			<div class="detail-row">
				<strong>{{ __('emails.label_phone') }}:</strong> {{ $user->phone }}
			</div>
			@endif
		</div>

		<div class="section-title" style="margin-top:6px;">
			<span style="font-size:18px;">🚀</span>
			<span>{{ __('emails.next_steps') }}</span>
		</div>
		<p class="helper-text" style="margin-bottom:10px;">
			{{ __('emails.next_steps_hint') }}
		</p>

		<div class="account-details" style="margin-top:8px;">
			<div class="section-title" style="margin:0 0 8px;">
				<span style="font-size:18px;">📌</span>
				<span>{{ __('emails.complete_registration') }}</span>
			</div>
			<p class="helper-text" style="margin:0 0 6px;">
				{{ __('emails.you_will_need') }}
			</p>
			<ul style="margin:0; {{ $emailPadInline }}:18px; font-size:14px; color:#4b5563;">
				<li>🏢 {{ __('emails.step_company_profile') }}</li>
				<li>📄 {{ __('emails.step_documents') }}</li>
				<li>🔍 {{ __('emails.step_verification') }}</li>
				<li>🎉 {{ __('emails.step_go_live') }}</li>
			</ul>
		</div>

		<div class="button-container">
			<a href="{{ $merchantRegistrationUrl }}" class="btn btn-primary" style="color:#ffffff;">
				🚀 {{ __('emails.btn_complete_registration') }}
			</a>
			<a href="{{ $loginUrl }}" class="btn btn-success" style="color:#ffffff;">
				🔐 {{ __('emails.btn_login') }}
			</a>
		</div>

		<div class="footer">
			@include('emails.partials.support-footer')
			<p style=" font-size:13px; color:#4b5563;">
				{{ __('emails.account_created_thanks') }}<br/>
				{{-- We’re excited to have you on board.<br/>
				<strong>Core Net Pay – Merchant Services</strong> --}}
			</p>
		</div>
	</div>
</body>
</html>


