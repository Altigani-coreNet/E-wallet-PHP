@include('emails.partials.locale')
<div class="support-footer" style="margin-top: 22px; padding-top: 14px; border-top: 1px solid #e5e7eb; font-size: 13px; color: #6b7280; direction: {{ $emailDir }}; text-align: {{ $emailAlign }};">
	<p style="margin: 0 0 6px; font-weight: 600; color: #374151;">{{ __('emails.support_title') }}</p>
	<p style="margin: 0 0 8px;">{{ __('emails.support_body') }}</p>
	<p style="margin: 0;">
		<span style="color:#6b7280;">{{ __('emails.support_email_label') }}:</span>
		<span style="color:#111827; font-weight:600;">support@corenetpay.com</span>
		&nbsp;&nbsp;
		<span style="color:#6b7280;">{{ __('emails.support_phone_label') }}:</span>
		<span style="color:#111827; font-weight:600;">+971 078975567</span>
		&nbsp;&nbsp;
		<span style="color:#6b7280;">{{ __('emails.support_chat_label') }}:</span>
		<a href="https://fastpos.sd" style="color:#0d6efd; font-weight:600; text-decoration:none;">fastpos.sd</a>
	</p>
</div>
