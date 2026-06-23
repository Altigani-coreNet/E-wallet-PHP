@include('emails.partials.locale')
<!DOCTYPE html>
<html lang="{{ $emailLocale }}" dir="{{ $emailDir }}">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>{{ __('emails.merchant_status_title') }}</title>
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
			max-width: 640px;
			margin: 0 auto;
			background-color: #ffffff;
			padding: 28px 24px 24px;
			border-radius: 12px;
			border: 1px solid #e5e7eb;
			box-shadow: 0 10px 30px rgba(15,23,42,0.35);
		}
		.status-section {
			margin: 24px 0;
			padding: 16px 18px;
			border-radius: 10px;
			background-color: #f8fafc;
			border: 1px solid #e2e8f0;
		}
		.status-section-title {
			font-weight: 600;
			margin-bottom: 12px;
			display: flex;
			align-items: center;
			gap: 8px;
		}
		.status-label {
			font-size: 13px;
			color: #6b7280;
			font-weight: 600;
			margin-bottom: 4px;
		}
		.status-badge {
			display: inline-block;
			padding: 6px 14px;
			border-radius: 999px;
			font-weight: 600;
			font-size: 12px;
		}
		.status-approved {
			background-color: #d1e7dd;
			color: #0f5132;
		}
		.status-rejected {
			background-color: #f8d7da;
			color: #842029;
		}
		.status-suspended {
			background-color: #fff3cd;
			color: #664d03;
		}
		.status-pending {
			background-color: #cff4fc;
			color: #055160;
		}
		.footer {
			margin-top: 24px;
			text-align: center;
			padding-top: 12px;
			color: #6c757d;
			font-size: 13px;
			border-top: 1px solid #e5e7eb;
		}
		.button {
			display: inline-block;
			padding: 12px 28px;
			background-color: #0d6efd;
			color: #ffffff;
			text-decoration: none;
			border-radius: 6px;
			margin: 8px 0 4px;
			font-weight: 600;
			font-size: 14px;
		}
		.section-title {
			font-size: 16px;
			font-weight: 600;
			margin: 22px 0 8px;
			display: flex;
			align-items: center;
			gap: 8px;
		}
		.subtle-divider {
			border-top: 1px solid #e5e7eb;
			margin: 20px 0 16px;
		}
		.muted {
			color: #6b7280;
			font-size: 13px;
		}
	</style>
</head>
<body style="direction: {{ $emailDir }}; text-align: {{ $emailAlign }};">
	<div class="card">
		<h1 style="text-align:center; margin-top:0; font-size:20px; margin-bottom:8px;">
			{{ __('emails.merchant_status_title') }}
		</h1>
		<p style="text-align:center; margin-top:0; margin-bottom:18px; font-size:14px; color:#4b5563;">
			{{ __('emails.merchant_status_intro', ['name' => $merchant->owner_name ?? $merchant->name]) }}
		</p>
		
		<div class="status-section">
			<div class="status-section-title">
				<span style="font-size:18px;">📊</span>
				<span>{{ __('emails.merchant_status_update_section') }}</span>
			</div>
			<div style="display:flex; flex-wrap:wrap; gap:16px;">
				<div style="flex:1 1 160px;">
					<div class="status-label">{{ __('emails.merchant_status_previous') }}</div>
					<span class="status-badge status-{{ $oldStatus ?? 'pending' }}">
						{{ __('emails.status_' . ($oldStatus ?? 'pending')) }}
					</span>
				</div>
				<div style="flex:1 1 160px;">
					<div class="status-label">{{ __('emails.merchant_status_current') }}</div>
					<span class="status-badge status-{{ $newStatus }}">
						{{ __('emails.status_' . $newStatus) }}
					</span>
				</div>
			</div>
		</div>

		<div style="margin: 18px 0; padding:14px 16px; border-radius:10px; background-color:#f9fafb; border:1px solid #e5e7eb;">
			<div class="section-title">
				<span style="font-size:18px;">🏢</span>
				<span>{{ __('emails.merchant_status_details') }}</span>
			</div>
			<div style="display:flex; flex-wrap:wrap; gap:16px; font-size:14px;">
				<div style="flex:1 1 200px;">
					<div class="status-label">{{ __('emails.merchant_status_business_name') }}</div>
					<div><strong>{{ $merchant->name }}</strong></div>
				</div>
				<div style="flex:1 1 160px;">
					<div class="status-label">{{ __('emails.merchant_status_merchant_code') }}</div>
					<div><strong>{{ $merchant->merchant_code }}</strong></div>
				</div>
				<div style="flex:1 1 200px;">
					<div class="status-label">{{ __('emails.merchant_status_business_type') }}</div>
					<div>{{ $merchant->business_type_display_name ?? 'N/A' }}</div>
				</div>
			</div>
		</div>

		<div class="section-title" style="margin-top:22px;">
			<span style="font-size:18px;">🚀</span>
			<span>{{ __('emails.merchant_status_go_live') }}</span>
		</div>
		<p class="muted" style="margin-bottom:10px;">
			{{ __('emails.merchant_status_go_live_body') }}
		</p>
		<ul style="margin-top:0; {{ $emailPadInline }}:18px; font-size:14px; color:#4b5563;">
			<li>{{ __('emails.merchant_status_go_live_1') }}</li>
			<li>{{ __('emails.merchant_status_go_live_2') }}</li>
			<li>{{ __('emails.merchant_status_go_live_3') }}</li>
			<li>{{ __('emails.merchant_status_go_live_4') }}</li>
			<li>{{ __('emails.merchant_status_go_live_5') }}</li>
		</ul>

		<div class="subtle-divider"></div>

		<div class="section-title" style="margin-top:0;">
			<span style="font-size:18px;">📥</span>
			<span>{{ __('emails.merchant_status_dashboard') }}</span>
		</div>
		<p class="muted" style="margin-bottom:10px;">
			{{ __('emails.merchant_status_dashboard_body') }}
		</p>

		<div style="text-align: center; margin: 30px 0;">
			<a href="{{ $dashboardUrl ?? 'https://fastpos.sd/sales/dashboard' }}" class="button" style="color:#ffffff;">{{ __('emails.merchant_status_btn_dashboard') }}</a>
		</div>

		@if($newStatus === 'approved')
			<div style="background-color: #d1e7dd; padding: 14px 16px; border-radius: 8px; margin: 10px 0 0;">
				<p style="color: #0f5132; margin: 0; font-size:13px;">
					<strong>🎉</strong> {{ __('emails.merchant_status_alert_approved') }}
				</p>
			</div>
		@elseif($newStatus === 'rejected')
			<div style="background-color: #f8d7da; padding: 14px 16px; border-radius: 8px; margin: 10px 0 0;">
				<p style="color: #842029; margin: 0; font-size:13px;">
					<strong>⚠️</strong> {{ __('emails.merchant_status_alert_rejected') }}
				</p>
			</div>
		@elseif($newStatus === 'suspended')
			<div style="background-color: #fff3cd; padding: 14px 16px; border-radius: 8px; margin: 10px 0 0;">
				<p style="color: #664d03; margin: 0; font-size:13px;">
					<strong>⚠️</strong> {{ __('emails.merchant_status_alert_suspended') }}
				</p>
			</div>
		@elseif($oldStatus === 'suspended' && $newStatus === 'approved')
			<div style="background-color: #d1e7dd; padding: 14px 16px; border-radius: 8px; margin: 10px 0 0;">
				<p style="color: #0f5132; margin: 0; font-size:13px;">
					<strong>✅</strong> {{ __('emails.merchant_status_alert_reactivated') }}
				</p>
			</div>
		@endif

		<div class="subtle-divider"></div>
		<div class="section-title" style="margin-top:0;">
			<span style="font-size:18px;">❓</span>
			<span>{{ __('emails.merchant_status_need_help') }}</span>
		</div>
		<p class="muted" style="margin-bottom:4px;">
			{{ __('emails.merchant_status_need_help_body') }}
		</p>
		<p class="muted" style="margin-top:0;">
			{{ __('emails.merchant_status_contact') }}
		</p>

		<div class="footer">
			<p>{{ __('emails.footer_rights', ['year' => date('Y'), 'app' => config('app.name')]) }}</p>
		</div>
	</div>
</body>
</html>


