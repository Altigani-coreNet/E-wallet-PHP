<?php

namespace App\Mail;

use App\Mail\Concerns\SetsMailLocale;
use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomerEmailVerificationMail extends Mailable
{
    use Queueable, SerializesModels, SetsMailLocale;

    public function __construct(
        public Customer $customer,
        public string $verifyUrl,
        public int $expiresInHours = 48,
    ) {
        $this->applyMailLocale();
    }

    public function build()
    {
        return $this->subject(__('emails.customer_email_verification_subject'))
            ->view('emails.customers.verify_email')
            ->with([
                'customer' => $this->customer,
                'verifyUrl' => $this->verifyUrl,
                'expiresInHours' => $this->expiresInHours,
            ]);
    }
}
