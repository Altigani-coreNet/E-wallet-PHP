<?php

namespace App\Mail;

use App\Mail\Concerns\SetsMailLocale;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentMail extends Mailable
{
    use Queueable, SerializesModels, SetsMailLocale;

    public $paymentLink;

    /**
     * Create a new message instance.
     */
    public function __construct($paymentLink)
    {
        $this->paymentLink = $paymentLink;
        $this->applyMailLocale();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.payment_subject', ['id' => $this->paymentLink->id ?? '']),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.payments',
            with: ['paymentLink' => $this->paymentLink],
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
