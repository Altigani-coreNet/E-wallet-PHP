<?php

namespace App\Mail;

use App\Mail\Concerns\SetsMailLocale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PartnerRegistrationContinuationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $firstName;
    public $lastName;
    public $businessName;
    public $email;

    /**
     * Create a new message instance.
     */
    public function __construct($firstName, $lastName, $businessName, $email)
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->businessName = $businessName;
        $this->email = $email;
        $this->applyMailLocale();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.partner_continuation_subject'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.partner-registration-continuation',
            with: [
                'firstName' => $this->firstName,
                'lastName' => $this->lastName,
                'businessName' => $this->businessName,
                'email' => $this->email,
            ]
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
