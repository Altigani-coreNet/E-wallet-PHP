<?php

namespace App\Mail;

use App\Mail\Concerns\SetsMailLocale;
use App\Models\Merchant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MerchantRejectionMail extends Mailable
{
    use Queueable, SerializesModels, SetsMailLocale;

    public $merchant;
    public $rejectionReason;

    /**
     * Create a new message instance.
     */
    public function __construct(Merchant $merchant, string $rejectionReason)
    {
        $this->merchant = $merchant;
        $this->rejectionReason = $rejectionReason;
        $this->applyMailLocale();
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject(__('emails.merchant_rejection_subject'))
            ->view('emails.merchants.rejection')
            ->with([
                'merchant' => $this->merchant,
                'rejectionReason' => $this->rejectionReason
            ]);
    }
}
