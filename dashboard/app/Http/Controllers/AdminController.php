<?php

namespace App\Http\Controllers;

use App\Models\BlacklistEntry;
use App\Models\BotUser;
use App\Models\PremiumGrant;
use App\Models\ShopItem;
use App\Models\TagReport;
use App\Services\Discord;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function overview(Discord $discord): View
    {
        $openReports = TagReport::query()->where('resolved', false)->count();
        $activeUsers = BotUser::query()->where('commands_used', '>', 0)->count();
        $shopActive = ShopItem::query()->where('active', true)->count();
        $shopRetired = ShopItem::query()->where('active', false)->count();
        $blockedUsers = BlacklistEntry::query()->whereNotNull('user')->count();
        $blockedGuilds = BlacklistEntry::query()->whereNotNull('guild')->count();
        $premium = PremiumGrant::query()->active()->count();

        return view('admin.overview', [
            'stats' => [
                ['label' => __('admin.overview_servers'), 'value' => rescue(fn () => count($discord->botGuildIds()), null, report: false)],
                ['label' => __('admin.overview_users'), 'value' => $activeUsers],
                ['label' => __('admin.overview_commands'), 'value' => (int) BotUser::query()->sum('commands_used')],
                ['label' => __('admin.overview_reports'), 'value' => $openReports, 'route' => 'admin.reports', 'alert' => $openReports > 0],
            ],
            'sections' => [
                [
                    'route' => 'admin.reports',
                    'icon' => 'alert',
                    'title' => __('admin.reports_title'),
                    'description' => __('admin.reports_description'),
                    'badge' => $openReports > 0 ? __('admin.overview_open', ['count' => $openReports]) : __('admin.overview_nothing_open'),
                    'alert' => $openReports > 0,
                ],
                [
                    'route' => 'admin.users',
                    'icon' => 'users',
                    'title' => __('admin.users_page_title'),
                    'description' => __('admin.users_page_description'),
                    'badge' => __('admin.overview_active_users', ['count' => number_format($activeUsers, 0, '', "\u{00A0}")]),
                ],
                [
                    'route' => 'admin.backgrounds',
                    'icon' => 'card',
                    'title' => __('admin.backgrounds_title'),
                    'description' => __('admin.backgrounds_description'),
                    'badge' => __('admin.overview_items', ['active' => $shopActive, 'retired' => $shopRetired]),
                ],
                [
                    'route' => 'admin.blacklist',
                    'icon' => 'lock',
                    'title' => __('admin.blacklist_title'),
                    'description' => __('admin.blacklist_description'),
                    'badge' => __('admin.overview_blocked', ['users' => $blockedUsers, 'guilds' => $blockedGuilds]),
                ],
                [
                    'route' => 'admin.premium',
                    'icon' => 'sparkles',
                    'title' => __('admin.premium_title'),
                    'description' => __('admin.premium_description'),
                    'badge' => __('admin.overview_premium', ['count' => $premium]),
                ],
            ],
        ]);
    }
}
