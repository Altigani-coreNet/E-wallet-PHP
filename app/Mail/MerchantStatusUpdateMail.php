<?php

namespace App\Mail;

use App\Mail\Concerns\SetsMailLocale;
use App\Models\Merchant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MerchantStatusUpdateMail extends Mailable
{
	use Queueable, SerializesModels, SetsMailLocale;

	public $merchant;
	public $oldStatus;
	public $newStatus;

	/**
	 * Create a new message instance.
	 */
	public function __construct(Merchant $merchant, $oldStatus, $newStatus)
	{
		$this->merchant = $merchant;
		$this->oldStatus = $oldStatus;
		$this->newStatus = $newStatus;
		$this->applyMailLocale();
	}

	/**
	 * Get the message envelope.
	 */
	public function envelope(): Envelope
	{
		$statusKey = 'emails.status_' . ($this->newStatus ?? 'pending');
		$statusText = __($statusKey);

		return new Envelope(
			subject: __('emails.merchant_status_subject', ['status' => $statusText]),
		);
	}

	/**
	 * Get the message content definition.
	 */
	public function content(): Content
	{
		$frontend = rtrim(config('app.frontend_url', config('app.url')), '/');
		return new Content(
			view: 'emails.merchant-status-update',
			with: [
				'merchant' => $this->merchant,
				'oldStatus' => $this->oldStatus,
				'newStatus' => $this->newStatus,
				'dashboardUrl' => $frontend . '/merchant/dashboard'
			],
		);
	}

	/**
	 * Get the attachments for the message.
	 *
	 * @return array<int, \Illuminate\Mail\Mailables\Attachment>
	 */
	public function attachments(): array
	{
		return [];
	}
}


