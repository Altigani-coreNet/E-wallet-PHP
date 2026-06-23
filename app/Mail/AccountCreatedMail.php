<?php

namespace App\Mail;

use App\Mail\Concerns\SetsMailLocale;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountCreatedMail extends Mailable
{
	use Queueable, SerializesModels, SetsMailLocale;

	public $user;
	public $userName;
	public $merchantRegistrationUrl;

	/**
	 * Create a new message instance.
	 */
	public function __construct(User $user, ?string $userName = null, ?string $locale = null)
	{
		$this->user = $user;
		$this->userName = $userName ?? $user->user_name;
		$frontend = rtrim(config('app.frontend_url', config('app.url')), '/');
		$this->merchantRegistrationUrl = $frontend . '/merchant-register';
		$this->applyMailLocale($locale);
	}

	/**
	 * Get the message envelope.
	 */
	public function envelope(): Envelope
	{
		return new Envelope(
			subject: __('emails.account_created_subject'),
		);
	}

	/**
	 * Get the message content definition.
	 */
	public function content(): Content
	{
		return new Content(
			view: 'emails.account-created',
			with: [
				'user' => $this->user,
				'userName' => $this->userName,
				'merchantRegistrationUrl' => $this->merchantRegistrationUrl,
				'loginUrl' => rtrim(config('app.frontend_url', config('app.url')), '/') . '/login'
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


