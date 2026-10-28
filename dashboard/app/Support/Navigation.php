<?php

namespace App\Support;

use App\Http\Middleware\EnsureBotOwner;
use App\Livewire\GuildSettings;

class Navigation
{
    public const USER_ROUTES = ['profile', 'profile.*', 'shop', 'tags'];

    public const INFO_ROUTES = ['info', 'info.*'];

    public const ADMIN_ROUTES = ['admin', 'admin.*'];

    public static function forGuild(array $guild): array
    {
        $id = $guild['id'];
        $manage = $guild['manage'] ?? true;

        $server = array_filter([
            $manage ? self::item(route('servers.show', $id), 'servers', __('menu.overview'), request()->routeIs('servers.show')) : null,
            self::item(route('servers.members', $id), 'users', __('moderation.members_title'), request()->routeIs('servers.members', 'servers.member')),
            self::item(route('servers.settings', [$id, GuildSettings::STARBOARD]), 'star', __('menu.section_starboard'), self::isSection(GuildSettings::STARBOARD)),
            ($guild['moderate'] ?? $manage) ? self::item(route('servers.modlog', $id), 'list', __('moderation.modlog_title'), request()->routeIs('servers.modlog')) : null,
            ($guild['tickets'] ?? $manage) ? self::item(route('servers.tickets', $id), 'history', __('tickets.history_title'), request()->routeIs('servers.tickets', 'servers.ticket')) : null,
            ($guild['appeals'] ?? $manage) ? self::item(route('servers.appeals', $id), 'gavel', __('tickets.appeals_list_title'), request()->routeIs('servers.appeals')) : null,
        ]);

        $groups = [self::group('server', __('menu.server'), $server, collapsible: false)];

        if (! $manage) {
            return $groups;
        }

        foreach (GuildSettings::GROUPS as $group => $sections) {
            $items = [];
            foreach ($sections as $section => $icon) {
                $items[] = self::item(route('servers.settings', [$id, $section]), $icon, __('menu.section_'.$section), self::isSection($section));
            }
            if ($group === 'general') {
                $items[] = self::item(route('servers.data', $id), 'database', __('menu.section_data'), request()->routeIs('servers.data'));
            }
            $groups[] = self::group($group, __('menu.group_'.$group), $items);
        }

        return $groups;
    }

    public static function forUser(): array
    {
        return [self::group('account', __('menu.account'), [
            self::item(route('profile'), 'profile', __('menu.profile'), request()->routeIs('profile')),
            self::item(route('profile.settings'), 'palette', __('menu.preferences'), request()->routeIs('profile.settings')),
            self::item(route('profile.premium'), 'heart', __('menu.premium'), request()->routeIs('profile.premium')),
            self::item(route('shop'), 'shop', __('menu.shop'), request()->routeIs('shop')),
            self::item(route('tags'), 'tag', __('menu.tags'), request()->routeIs('tags')),
            self::item(route('profile.data'), 'database', __('menu.section_data'), request()->routeIs('profile.data')),
        ], collapsible: false)];
    }

    public static function forInfo(): array
    {
        return [self::group('info', __('menu.info'), [
            self::item(route('info'), 'info', __('info.general_title'), request()->routeIs('info')),
            self::item(route('info.commands'), 'terminal', __('info.commands_title'), request()->routeIs('info.commands')),
            self::item(route('info.system'), 'cpu', __('info.system_title'), request()->routeIs('info.system')),
        ], collapsible: false)];
    }

    public static function forAdmin(): array
    {
        return [self::group('admin', __('menu.admin'), [
            self::item(route('admin'), 'alert', __('admin.reports_title'), request()->routeIs('admin')),
            self::item(route('admin.backgrounds'), 'card', __('admin.backgrounds_title'), request()->routeIs('admin.backgrounds')),
            self::item(route('admin.users'), 'coin', __('admin.users_title'), request()->routeIs('admin.users')),
            self::item(route('admin.blacklist'), 'lock', __('admin.blacklist_title'), request()->routeIs('admin.blacklist')),
            self::item(route('admin.premium'), 'sparkles', __('admin.premium_title'), request()->routeIs('admin.premium')),
        ], collapsible: false)];
    }

    public static function userMenu(): array
    {
        return array_filter([
            self::item(route('servers.index'), 'servers', __('menu.servers'), request()->routeIs('servers.*')),
            self::item(route('profile'), 'profile', __('menu.account'), request()->routeIs(...self::USER_ROUTES)),
            self::item(route('info'), 'info', __('menu.info'), request()->routeIs(...self::INFO_ROUTES)),
            EnsureBotOwner::check(auth()->id())
                ? self::item(route('admin'), 'shield', __('menu.admin'), request()->routeIs(...self::ADMIN_ROUTES))
                : null,
        ]);
    }

    private static function isSection(string $section): bool
    {
        return request()->routeIs('servers.settings') && request()->route('section') === $section;
    }

    private static function item(string $url, string $icon, string $label, bool $active): array
    {
        return compact('url', 'icon', 'label', 'active');
    }

    private static function group(string $key, string $label, array $items, bool $collapsible = true): array
    {
        $items = array_values($items);

        return [
            'key' => $key,
            'label' => $label,
            'items' => $items,
            'collapsible' => $collapsible,
            'active' => collect($items)->contains('active', true),
        ];
    }
}
