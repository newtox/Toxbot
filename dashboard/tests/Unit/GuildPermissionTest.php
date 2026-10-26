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

    public function test_moderation_rights_open_the_moderation_pages(): void
    {
        foreach ([0x2, 0x4, 0x80, 0x10000000000, 0x20] as $bit) {
            $this->assertTrue(GuildAccess::canModerateByPermissions(['permissions' => (string) $bit]));
        }
        $this->assertFalse(GuildAccess::canModerateByPermissions(['permissions' => (string) (0x400 | 0x800 | 0x10)]));
    }

    public function test_appeals_need_ban_members(): void
    {
        $this->assertTrue(GuildAccess::canHandleAppealsByPermissions(['permissions' => (string) 0x4]));
        $this->assertTrue(GuildAccess::canHandleAppealsByPermissions(['permissions' => (string) 0x20]));
        $this->assertFalse(GuildAccess::canHandleAppealsByPermissions(['permissions' => (string) (0x2 | 0x10000000000)]));
    }

    public function test_actions_follow_the_single_permissions(): void
    {
        $this->assertSame(['kick'], GuildAccess::actionsByPermissions(['permissions' => (string) 0x2]));
        $this->assertSame(['ban', 'unban'], GuildAccess::actionsByPermissions(['permissions' => (string) 0x4]));
        $this->assertSame(['warn', 'timeout', 'untimeout'], GuildAccess::actionsByPermissions(['permissions' => (string) 0x10000000000]));
        $this->assertSame([], GuildAccess::actionsByPermissions(['permissions' => (string) 0x20]));
        $this->assertCount(6, GuildAccess::actionsByPermissions(['permissions' => (string) 0x8]));
        $this->assertCount(6, GuildAccess::actionsByPermissions(['owner' => true, 'permissions' => '0']));
    }
}
