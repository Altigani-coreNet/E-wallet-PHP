<?php

namespace App\Mail;

use App\Mail\Concerns\SetsMailLocale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerificationCode extends Mailable
{
    use Queueable, SerializesModels, SetsMailLocale;

    public $code;

    public function __construct($code)
    {
        $this->code = $code;
        $this->applyMailLocale();
    }

    public function build()
    {
        return $this->view('emails.verification-code')
            ->subject(__('emails.verification_code_subject'));
    }
}
