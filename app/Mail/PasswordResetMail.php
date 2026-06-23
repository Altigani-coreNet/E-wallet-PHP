<?php

namespace App\Mail;

use App\Mail\Concerns\SetsMailLocale;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels, SetsMailLocale;

    public User $user;
    public string $resetUrl;

    public function __construct(User $user, string $resetUrl)
    {
        $this->user = $user;
        $this->resetUrl = $resetUrl;
        $this->applyMailLocale();
    }

    public function build()
    {
        return $this->subject(__('emails.password_reset_subject'))
            ->view('emails.users.password_reset')
            ->with([
                'user' => $this->user,
                'resetUrl' => $this->resetUrl,
            ]);
    }
}
