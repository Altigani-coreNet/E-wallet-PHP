<?php

namespace App\Console\Commands;

use App\Events\BranchStatusChanged;
use App\Models\Branch;
use App\Models\Merchant;
use Illuminate\Console\Command;

class TestBranchStatusEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:branch-email {branch_id} {new_status}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test branch status change email functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $branchId = $this->argument('branch_id');
        $newStatus = $this->argument('new_status');

        $branch = Branch::find($branchId);
        
        if (!$branch) {
            $this->error("Branch with ID {$branchId} not found!");
            return 1;
        }

        $merchant = $branch->merchant;
        
        if (!$merchant) {
            $this->error("Merchant not found for branch {$branch->name}!");
            return 1;
        }

        if (!$merchant->email) {
            $this->error("Merchant {$merchant->name} has no email address!");
            return 1;
        }

        $this->info("Testing email for:");
        $this->info("Branch: {$branch->name}");
        $this->info("Merchant: {$merchant->name} ({$merchant->email})");
        $this->info("Old Status: {$branch->status}");
        $this->info("New Status: {$newStatus}");

        if ($this->confirm('Do you want to proceed with sending the test email?')) {
            try {
                // Dispatch the event
                event(new BranchStatusChanged($branch, $branch->status, $newStatus));
                
                $this->info('✅ Event dispatched successfully!');
                $this->info('Check your email and logs for confirmation.');
                
            } catch (\Exception $e) {
                $this->error('❌ Error dispatching event: ' . $e->getMessage());
                return 1;
            }
        } else {
            $this->info('Test cancelled.');
        }

        return 0;
    }
}
