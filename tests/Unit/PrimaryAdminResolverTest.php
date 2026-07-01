<?php

namespace Tests\Unit;

use App\Models\Admin;
use App\Modules\AdminKyc\Services\PrimaryAdminResolver;
use Tests\CustomerAuthTestCase;

class PrimaryAdminResolverTest extends CustomerAuthTestCase
{

    public function test_resolves_oldest_active_admin(): void
    {
        Admin::query()->delete();

        $older = Admin::factory()->active()->create(['created_at' => now()->subDays(2)]);
        Admin::factory()->active()->create(['created_at' => now()->subDay()]);

        $resolved = PrimaryAdminResolver::resolve();

        $this->assertNotNull($resolved);
        $this->assertSame((string) $older->id, (string) $resolved->id);
    }

    public function test_skips_inactive_admins_when_resolving_by_id_config(): void
    {
        Admin::query()->delete();

        $inactive = Admin::factory()->create([
            'status' => 'inactive',
            'created_at' => now()->subDays(3),
        ]);

        config(['notifications.admin_kyc.recipient' => (string) $inactive->id]);

        $this->assertNull(PrimaryAdminResolver::resolve());
    }
}
