<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Reset Your Password</title>
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
	</style>
</head>
<body>
	<div class="card">
		<h2 style="text-align:left; margin-top:0; font-size:18px;">
			🔐 Password Reset Request
		</h2>

		<p style="font-size:14px; margin-bottom:10px;">
			Hello {{ $user->name ?? $user->first_name ?? 'User' }},
		</p>
		<p style="font-size:14px; margin-top:0; margin-bottom:16px;">
			We received a request to reset the password for your <strong>Core Net Pay</strong> account.
			If you made this request, please click the secure link below to create a new password.
		</p>

		<div class="inner-card">
			<div class="section-title" style="margin-top:0;">
				<span style="font-size:18px;">🔁</span>
				<span>Reset Your Password</span>
			</div>
			<p class="helper-text" style="margin-bottom:8px;">
				Click the button below to continue:
			</p>
			<a href="{{ $resetUrl }}" class="button" style="color:#ffffff;">RESET PASSWORD</a>
			<p class="helper-text" style="margin-top:8px; margin-bottom:0;">
				This link is valid for <strong>15 minutes</strong> for your security.
				If it expires, you can request a new reset link directly from the login page.
			</p>
		</div>

		<div class="section-title">
			<span style="font-size:18px;">⚠️</span>
			<span>Didn’t Request This?</span>
		</div>
		<p class="helper-text">
			If you did not request a password reset, please ignore this email. Your account will remain secure
			and no changes will be made.
		</p>

		<div class="section-title">
			<span style="font-size:18px;">🛡</span>
			<span>Security Reminder</span>
		</div>
		<p class="helper-text" style="margin-bottom:6px;">
			For your safety:
		</p>
		<ul style="margin-top:0; padding-left:18px; font-size:14px; color:#4b5563;">
			<li>Never share your password or OTP with anyone.</li>
			<li>Always use a strong and unique password.</li>
			<li>Ensure you are logging in from the official Core Net Pay website/app.</li>
		</ul>

		<div class="footer">
			<p style="margin-bottom:4px;">
				Thank you for using <strong>Core Net Pay</strong>. If you need help, our support team is always available.
			</p>
			<p style="margin:0;">Email: <strong>support@fastpos.sd</strong></p>
			<p style="margin:0;">Phone: <strong>+971 4567890</strong> &nbsp;•&nbsp; Live Chat: Available on our website</p>
			<p style="margin:10px 0 0; font-weight:600;">Core Net Pay – Support Team</p>
			<p style="margin:4px 0 0; font-size:12px; color:#9ca3af;">
				This is an automated message. Please do not reply to this email.
			</p>
		</div>
	</div>
</body>
</html>

