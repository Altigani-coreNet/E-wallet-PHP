<?php

namespace App\Mail;

use App\Mail\Concerns\SetsMailLocale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MerchantRegistrationContinuationMail extends Mailable
{
    use Queueable, SerializesModels, SetsMailLocale;

    public $firstName;

    public $lastName;

    public $businessName;

    public $email;

    public function __construct($firstName, $lastName, $businessName, $email)
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->businessName = $businessName;
        $this->email = $email;
        $this->applyMailLocale();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.merchant_continuation_subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.merchant-registration-continuation',
            with: [
                'firstName' => $this->firstName,
                'lastName' => $this->lastName,
                'businessName' => $this->businessName,
                'email' => $this->email,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
