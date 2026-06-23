<?php

namespace App\Mail;

use App\Mail\Concerns\SetsMailLocale;
use App\Models\User;
use App\Models\Merchant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MerchantRegistrationConfirmationMail extends Mailable
{
    use Queueable, SerializesModels, SetsMailLocale;

    public $user;
    public $merchant;

    /**
     * Create a new message instance.
     *
     * @param User $user
     * @param Merchant $merchant
     */
    public function __construct(User $user, Merchant $merchant)
    {
        $this->user = $user;
        $this->merchant = $merchant;
        $this->applyMailLocale();
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject(__('emails.merchant_registration_confirmation_subject', ['app' => config('app.name')]))
                    ->view('emails.merchant.registration_confirmation');
    }
}
