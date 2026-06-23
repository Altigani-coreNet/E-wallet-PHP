<?php

namespace App\Mail;

use App\Mail\Concerns\SetsMailLocale;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewUserCredentialsMail extends Mailable
{
    use Queueable, SerializesModels, SetsMailLocale;

    public User $user;
    public string $plainPassword;

    public function __construct(User $user, string $plainPassword)
    {
        $this->user = $user;
        $this->plainPassword = $plainPassword;
        $this->applyMailLocale();
    }

    public function build()
    {
        return $this->subject(__('emails.new_credentials_subject'))
            ->view('emails.users.new_credentials')
            ->with([
                'user' => $this->user,
                'plainPassword' => $this->plainPassword,
            ]);
    }
}


