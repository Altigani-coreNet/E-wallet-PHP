<?php

namespace App\Listeners;

use App\Events\MerchantStatusChanged;
use App\Mail\MerchantStatusUpdateMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendMerchantStatusEmail implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(MerchantStatusChanged $event): void
    {
        $merchant = $event->merchant;
        
        // Send email to the merchant's email address
        if ($merchant->email) {
            Mail::to($merchant->email)
                ->send(new MerchantStatusUpdateMail($merchant, $event->oldStatus, $event->newStatus));
        }
        
        // Also send email to the owner if they have a different email
        // You can add an owner_email field to the merchants table if needed
        // For now, we'll send to the merchant email (which is typically the owner's email)
        
        // If you want to add owner_email field later, uncomment this:
        /*
        if ($merchant->owner_email && $merchant->owner_email !== $merchant->email) {
            Mail::to($merchant->owner_email)
                ->send(new MerchantStatusUpdateMail($merchant, $event->oldStatus, $event->newStatus));
        }
        */
    }
}
