<?php

namespace App\Listeners;

use App\Events\BranchStatusChanged;
use App\Jobs\SendBranchStatusEmailJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendBranchStatusEmail implements ShouldQueue
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
    public function handle(BranchStatusChanged $event): void
    {
        try {
            $branch = $event->branch;
            $merchant = $branch->merchant;
            
            // Check if merchant exists and has email
            if (!$merchant || !$merchant->email) {
                Log::warning('Cannot send branch status email: Merchant not found or no email', [
                    'branch_id' => $branch->id,
                    'merchant_id' => $branch->merchant_id,
                    'merchant_email' => $merchant->email ?? 'null'
                ]);
                return;
            }

            // Dispatch the job to handle email sending asynchronously
            SendBranchStatusEmailJob::dispatch($branch, $merchant, $event->oldStatus, $event->newStatus);

            Log::info('Branch status email job dispatched successfully', [
                'branch_id' => $branch->id,
                'branch_name' => $branch->name,
                'merchant_id' => $merchant->id,
                'merchant_email' => $merchant->email,
                'old_status' => $event->oldStatus,
                'new_status' => $event->newStatus
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to dispatch branch status email job', [
                'branch_id' => $event->branch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
