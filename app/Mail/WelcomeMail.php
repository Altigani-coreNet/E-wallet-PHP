<?php

namespace App\Mail;

use App\Mail\Concerns\SetsMailLocale;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels, SetsMailLocale;

    public $user;
    public $password;
    public $merchant;

    /**
     * Create a new message instance.
     */
    public function __construct($user, $password, $merchant)
    {
        $this->user = $user;
        $this->password = $password;
        $this->merchant = $merchant;
        $this->applyMailLocale();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.welcome_subject', ['merchant' => $this->merchant->name]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome',
            with: [
                'user' => $this->user,
                'password' => $this->password,
                'merchant' => $this->merchant
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
