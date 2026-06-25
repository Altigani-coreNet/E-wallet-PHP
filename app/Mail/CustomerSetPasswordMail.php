<?php

namespace App\Mail;

use App\Mail\Concerns\SetsMailLocale;
use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomerSetPasswordMail extends Mailable
{
    use Queueable, SerializesModels, SetsMailLocale;

    public Customer $customer;

    public string $setupUrl;

    public int $expiresInHours;

    public function __construct(Customer $customer, string $setupUrl, int $expiresInHours = 48)
    {
        $this->customer = $customer;
        $this->setupUrl = $setupUrl;
        $this->expiresInHours = $expiresInHours;
        $this->applyMailLocale();
    }

    public function build()
    {
        return $this->subject(__('emails.customer_set_password_subject'))
            ->view('emails.customers.set_password')
            ->with([
                'customer' => $this->customer,
                'setupUrl' => $this->setupUrl,
                'expiresInHours' => $this->expiresInHours,
            ]);
    }
}
