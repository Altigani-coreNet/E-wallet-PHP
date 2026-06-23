<?php

namespace App\Mail;

use App\Mail\Concerns\SetsMailLocale;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetSuccessMail extends Mailable
{
	use Queueable, SerializesModels, SetsMailLocale;

	public User $user;

	public function __construct(User $user)
	{
		$this->user = $user;
		$this->applyMailLocale();
	}

	public function build()
	{
		return $this->subject(__('emails.password_reset_success_subject'))
			->view('emails.users.password_reset_success')
			->with([
				'user' => $this->user,
			]);
	}
}


