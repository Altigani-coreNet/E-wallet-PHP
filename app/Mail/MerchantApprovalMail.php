<?php

namespace App\Mail;

use App\Mail\Concerns\SetsMailLocale;
use App\Models\User;
use App\Models\Merchant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MerchantApprovalMail extends Mailable
{
    use Queueable, SerializesModels, SetsMailLocale;

    public $user;
    public $password;
    public $merchant;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $password, Merchant $merchant)
    {
        $this->user = $user;
        $this->password = $password;
        $this->merchant = $merchant;
        $this->applyMailLocale();
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject(__('emails.merchant_approval_subject'))
                    ->view('emails.merchant.approval')
                    ->with([
                        'user' => $this->user,
                        'password' => $this->password,
                        'merchant' => $this->merchant,
                    ]);
    }
}


