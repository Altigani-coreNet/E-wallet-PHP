<?php

namespace App\Mail;

use App\Mail\Concerns\SetsMailLocale;
use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomerApprovalMail extends Mailable
{
    use Queueable, SerializesModels, SetsMailLocale;

    public function __construct(
        public Customer $customer,
    ) {
        $this->applyMailLocale();
    }

    public function build()
    {
        return $this->subject(__('emails.customer_approval_subject'))
            ->view('emails.customers.approval')
            ->with([
                'customer' => $this->customer,
            ]);
    }
}
