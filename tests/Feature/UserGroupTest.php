<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\UserGroup;
use App\Models\Merchant;
use App\Models\User;
use App\Models\Terminal;
use App\Models\TerminalGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserGroupTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_group_can_be_created()
    {
        $merchant = Merchant::factory()->create();
        $user = User::factory()->create(['merchant_id' => $merchant->id]);

        $userGroupData = [
            'name' => 'Test User Group',
            'merchant_id' => $merchant->id,
            'description' => 'Test description',
            'is_single_terminal' => false,
            'user_ids' => [$user->id],
        ];

        $userGroup = UserGroup::create($userGroupData);

        $this->assertDatabaseHas('user_groups', [
            'name' => 'Test User Group',
            'merchant_id' => $merchant->id,
            'description' => 'Test description',
            'is_single_terminal' => false,
        ]);

        $this->assertTrue($userGroup->users->contains($user));
    }

    public function test_user_group_with_single_terminal_mode()
    {
        $merchant = Merchant::factory()->create();
        $user = User::factory()->create(['merchant_id' => $merchant->id]);
        $terminal = Terminal::factory()->create(['merchant_id' => $merchant->id]);

        $userGroupData = [
            'name' => 'Single Terminal Group',
            'merchant_id' => $merchant->id,
            'description' => 'Test single terminal group',
            'is_single_terminal' => true,
            'terminal_id' => $terminal->id,
            'user_ids' => [$user->id],
        ];

        $userGroup = UserGroup::create($userGroupData);

        $this->assertTrue($userGroup->is_single_terminal);
        $this->assertEquals($terminal->id, $userGroup->terminal_id);
        $this->assertTrue($userGroup->users->contains($user));
    }

    public function test_user_group_with_multiple_terminal_groups()
    {
        $merchant = Merchant::factory()->create();
        $user = User::factory()->create(['merchant_id' => $merchant->id]);
        $terminalGroup1 = TerminalGroup::factory()->create(['merchant_id' => $merchant->id]);
        $terminalGroup2 = TerminalGroup::factory()->create(['merchant_id' => $merchant->id]);

        $userGroupData = [
            'name' => 'Multiple Terminal Groups',
            'merchant_id' => $merchant->id,
            'description' => 'Test multiple terminal groups',
            'is_single_terminal' => false,
            'user_ids' => [$user->id],
        ];

        $userGroup = UserGroup::create($userGroupData);
        $userGroup->terminalGroups()->attach([$terminalGroup1->id, $terminalGroup2->id]);

        $this->assertFalse($userGroup->is_single_terminal);
        $this->assertNull($userGroup->terminal_id);
        $this->assertEquals(2, $userGroup->terminalGroups->count());
        $this->assertTrue($userGroup->users->contains($user));
    }

    public function test_user_group_status_toggle()
    {
        $merchant = Merchant::factory()->create();
        $userGroup = UserGroup::factory()->create([
            'merchant_id' => $merchant->id,
            'is_active' => true,
        ]);

        $this->assertTrue($userGroup->is_active);

        $userGroup->update(['is_active' => false]);
        $this->assertFalse($userGroup->is_active);

        $userGroup->update(['is_active' => true]);
        $this->assertTrue($userGroup->is_active);
    }

    public function test_user_group_relationships()
    {
        $merchant = Merchant::factory()->create();
        $user = User::factory()->create(['merchant_id' => $merchant->id]);
        $terminalGroup = TerminalGroup::factory()->create(['merchant_id' => $merchant->id]);

        $userGroup = UserGroup::factory()->create([
            'merchant_id' => $merchant->id,
            'is_single_terminal' => false,
        ]);

        $userGroup->users()->attach($user->id);
        $userGroup->terminalGroups()->attach($terminalGroup->id);

        $this->assertTrue($userGroup->merchant->is($merchant));
        $this->assertTrue($userGroup->users->contains($user));
        $this->assertTrue($userGroup->terminalGroups->contains($terminalGroup));
    }
} 