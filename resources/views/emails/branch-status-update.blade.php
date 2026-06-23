@include('emails.partials.locale')
<!DOCTYPE html>
<html lang="{{ $emailLocale }}" dir="{{ $emailDir }}">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>{{ __('emails.branch_status_title') }}</title>
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
			padding: 20px;
			border-radius: 10px;
			box-shadow: 0 0 20px rgba(15,23,42,0.35);
		}
		.status-badge {
			display: inline-block;
			padding: 5px 15px;
			border-radius: 20px;
			font-weight: bold;
			font-size: 12px;
		}
		.status-pending { background-color: #fff3cd; color: #856404; }
		.status-approved { background-color: #d1e7dd; color: #0f5132; }
		.status-rejected { background-color: #f8d7da; color: #842029; }
		.status-suspended { background-color: #fff3cd; color: #664d03; }
		.branch-info {
			background-color: #f8f9fa;
			padding: 15px;
			border-radius: 5px;
			margin: 15px 0;
		}
		.action-button {
			display: inline-block;
			padding: 10px 20px;
			background-color: #0d6efd;
			color: white;
			text-decoration: none;
			border-radius: 5px;
			margin: 15px 0;
		}
	</style>
</head>
<body style="direction: {{ $emailDir }}; text-align: {{ $emailAlign }};">
	<div class="card">
		<h2 style="text-align:center; margin-top:0;">{{ __('emails.branch_status_title') }}</h2>
		<p>{{ __('emails.branch_status_greeting', ['name' => $merchant->name]) }}</p>
		<p>{{ __('emails.branch_status_intro') }}</p>

		<div class="branch-info">
			<h3>{{ __('emails.branch_status_info') }}</h3>
			<p><strong>{{ __('emails.branch_status_name') }}:</strong> {{ $branch->name }}</p>
			<p><strong>{{ __('emails.branch_status_address') }}:</strong> {{ $branch->address ?? __('emails.branch_status_address_na') }}</p>
			<p><strong>{{ __('emails.branch_status_previous') }}:</strong>
				<span class="status-badge status-{{ $oldStatus }}">{{ __('emails.status_' . $oldStatus) }}</span>
			</p>
			<p><strong>{{ __('emails.branch_status_new') }}:</strong>
				<span class="status-badge status-{{ $newStatus }}">{{ __('emails.status_' . $newStatus) }}</span>
			</p>
			<p><strong>{{ __('emails.branch_status_updated_at') }}:</strong> {{ $branch->updated_at->format('Y-m-d H:i') }}</p>
		</div>

		@if($newStatus === 'approved')
			<p>🎉 {{ __('emails.branch_status_approved') }}</p>
			<p>{{ __('emails.branch_status_approved_body') }}</p>
		@elseif($newStatus === 'rejected')
			<p>❌ {{ __('emails.branch_status_rejected') }}</p>
		@elseif($newStatus === 'suspended')
			<p>⚠️ {{ __('emails.branch_status_suspended') }}</p>
			<p>{{ __('emails.branch_status_suspended_detail') }}</p>
		@elseif($newStatus === 'pending')
			<p>⏳ {{ __('emails.branch_status_pending') }}</p>
		@endif

		<p>{{ __('emails.branch_status_contact') }}</p>

		<a href="{{ $branchesUrl ?? '#' }}" class="action-button" style="color:#ffffff;">{{ __('emails.branch_status_btn') }}</a>

		<p>{{ __('emails.branch_status_regards') }}<br>
		<strong>{{ __('emails.branch_status_team', ['app' => config('app.name')]) }}</strong></p>

		<div class="footer" style="margin-top:24px; text-align:center; font-size:12px; color:#6c757d;">
			<p>{{ __('emails.footer_rights', ['year' => date('Y'), 'app' => config('app.name')]) }}</p>
		</div>
	</div>
</body>
</html>
