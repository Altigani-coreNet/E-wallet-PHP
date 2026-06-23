<?php

namespace App\Console\Commands;

use App\Models\Merchant;
use App\Models\User;
use Illuminate\Console\Command;

class ApproveDemoMerchants extends Command
{
    protected $signature = 'merchants:approve-demo {--emails=* : Optional email list; defaults to CREDENTIALS demo accounts}';

    protected $description = 'Set demo merchant accounts to approved (fixes "Account under review" in Dashboards)';

    /** @var list<string> */
    private const DEFAULT_EMAILS = [
        'retail@corenet.com',
        'electronics@corenet.com',
        'pharmasy@corenet.com',
        'services@corenet.com',
        'restaurant@corenet.com',
        'fashion@corenet.com',
        'cityhub@corenet.com',
        'john@techsolutions.com',
    ];

    public function handle(): int
    {
        $emails = $this->option('emails');
        if (! is_array($emails) || $emails === []) {
            $emails = self::DEFAULT_EMAILS;
        }

        $merchants = Merchant::withoutGlobalScopes()
            ->whereIn('email', $emails)
            ->get();

        if ($merchants->isEmpty()) {
            $this->warn('No merchants found for: '.implode(', ', $emails));
            $this->line('Run: php artisan db:seed --class=MerchantSeeder');

            return self::FAILURE;
        }

        foreach ($merchants as $merchant) {
            $merchant->forceFill([
                'status' => 'approved',
                'is_active' => true,
            ])->save();

            User::withoutGlobalScopes()
                ->where('id', $merchant->user_id)
                ->orWhere('email', $merchant->email)
                ->update(['status' => true]);

            $type = $merchant->business_type;
            $typeLabel = is_object($type) && property_exists($type, 'value') ? $type->value : (string) $type;
            $this->info("Approved: {$merchant->email} ({$typeLabel})");
        }

        $this->newLine();
        $this->comment('Dashboards only unlocks POS/Sales when merchant.status === "approved" (see merchantLoginAuthService.js).');

        return self::SUCCESS;
    }
}
