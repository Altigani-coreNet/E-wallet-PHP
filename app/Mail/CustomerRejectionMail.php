<?php

namespace App\Mail;

use App\Mail\Concerns\SetsMailLocale;
use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomerRejectionMail extends Mailable
{
    use Queueable, SerializesModels, SetsMailLocale;

    public function __construct(
        public Customer $customer,
        public string $rejectionReason,
    ) {
        $this->applyMailLocale();
    }

    public function build()
    {
        return $this->subject(__('emails.customer_rejection_subject'))
            ->view('emails.customers.rejection')
            ->with([
                'customer' => $this->customer,
                'rejectionReason' => $this->rejectionReason,
            ]);
    }
}
