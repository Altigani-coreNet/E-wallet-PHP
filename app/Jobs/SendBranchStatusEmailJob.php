<?php

namespace App\Jobs;

use App\Events\BranchStatusChanged;
use App\Mail\BranchStatusUpdateMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendBranchStatusEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $tries = 3;

    protected $branch;
    protected $merchant;
    protected $oldStatus;
    protected $newStatus;

    /**
     * Create a new job instance.
     */
    public function __construct($branch, $merchant, $oldStatus, $newStatus)
    {
        $this->branch = $branch;
        $this->merchant = $merchant;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Check if merchant exists and has email
            if (!$this->merchant || !$this->merchant->email) {
                Log::warning('Cannot send branch status email: Merchant not found or no email', [
                    'branch_id' => $this->branch->id,
                    'merchant_id' => $this->branch->merchant_id,
                    'merchant_email' => $this->merchant->email ?? 'null'
                ]);
                return;
            }

            // Send email to merchant
            Mail::to($this->merchant->email)->send(new BranchStatusUpdateMail(
                $this->branch,
                $this->merchant,
                $this->oldStatus,
                $this->newStatus
            ));

            Log::info('Branch status email sent successfully via queue job', [
                'branch_id' => $this->branch->id,
                'branch_name' => $this->branch->name,
                'merchant_id' => $this->merchant->id,
                'merchant_email' => $this->merchant->email,
                'old_status' => $this->oldStatus,
                'new_status' => $this->newStatus
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send branch status email via queue job', [
                'branch_id' => $this->branch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Re-throw the exception to trigger retry logic
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Branch status email job failed permanently', [
            'branch_id' => $this->branch->id,
            'merchant_id' => $this->merchant->id,
            'error' => $exception->getMessage()
        ]);
    }
}
