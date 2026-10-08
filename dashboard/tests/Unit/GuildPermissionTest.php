<?php

namespace Tests\Unit;

use App\Services\GuildAccess;
use PHPUnit\Framework\TestCase;

class GuildPermissionTest extends TestCase
{
    public function test_owner_admin_and_manage_guild_may_manage(): void
    {
        $this->assertTrue(GuildAccess::canManageByPermissions(['owner' => true, 'permissions' => '0']));
        $this->assertTrue(GuildAccess::canManageByPermissions(['permissions' => (string) 0x8]));
        $this->assertTrue(GuildAccess::canManageByPermissions(['permissions' => (string) 0x20]));
        $this->assertTrue(GuildAccess::canManageByPermissions(['permissions' => '2251799813685247']));
    }

    public function test_normal_members_may_not_manage(): void
    {
        $this->assertFalse(GuildAccess::canManageByPermissions(['permissions' => '0']));
        $this->assertFalse(GuildAccess::canManageByPermissions(['permissions' => (string) (0x400 | 0x800)]));
        $this->assertFalse(GuildAccess::canManageByPermissions([]));
    }
}
