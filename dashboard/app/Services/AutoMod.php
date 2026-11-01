<?php

namespace App\Services;

use App\Models\Guild;
use App\Support\DomainList;
use DomainException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AutoMod
{
    public const MAX_KEYWORD_LENGTH = 60;

    public const MAX_KEYWORDS = 1000;

    public const MAX_ALLOWED = 100;

    public const MAX_PRESET_ALLOWED = 1000;

    public const MAX_EXEMPT_ROLES = 20;

    public const MAX_EXEMPT_CHANNELS = 50;

    public const MAX_MESSAGE = 150;

    public const MIN_MENTIONS = 3;

    public const MAX_MENTIONS = 50;

    public const MENTION_LIMITS = [3, 5, 8, 10, 15, 20, 30, 50];

    public const TIMEOUTS = [0, 60, 300, 600, 3600, 86400, 604800];

    public const PRESETS = [1, 2, 3];

    public const RULES = [
        'domains' => 'automod_rule_domains',
        'words' => 'automod_rule_words',
        'mentions' => 'automod_rule_mentions',
        'spam' => 'automod_rule_spam',
        'presets' => 'automod_rule_presets',
        'profile' => 'automod_rule_profile',
    ];

    public const TRIGGERS = [
        'domains' => 1,
        'words' => 1,
        'spam' => 3,
        'presets' => 4,
        'mentions' => 5,
        'profile' => 6,
    ];

    private const KEYWORD_RULES_PER_SERVER = 6;

    public function __construct(private readonly Discord $discord) {}

    public static function words(?string $input): array
    {
        $words = preg_split('/[\r\n,]+/u', (string) $input) ?: [];

        return array_values(array_unique(array_filter(array_map(fn ($word) => mb_strtolower(trim($word)), $words), fn ($word) => $word !== '')));
    }

    public const TEMPLATES = ['scam', 'insults', 'staff'];

    public static function template(string $template, string $locale): array
    {
        $words = (array) trans('automod_templates.'.$template, [], $locale);
        if ($template !== 'insults' && $locale !== 'en') {
            $words = array_merge($words, (array) trans('automod_templates.'.$template, [], 'en'));
        }

        return array_values(array_unique(array_map('mb_strtolower', $words)));
    }

    public static function merge(?string $current, array $words): string
    {
        return implode("\n", array_values(array_unique(array_merge(self::words($current), $words))));
    }

    public static function ids(?string $input): array
    {
        return array_values(array_filter(explode(',', (string) $input)));
    }

    public function sync(Guild $guild): array
    {
        $failed = [];
        $existing = null;

        foreach (self::RULES as $key => $column) {
            $rule = $this->desired($guild, $key);

            if ($rule === null && ! $guild->{$column} && $existing === null) {
                continue;
            }

            try {
                $existing ??= $this->discord->autoModRules($guild->id);
                $guild->{$column} = $this->apply($guild->id, $key, $guild->{$column}, $rule, $existing);
            } catch (RequestException $e) {
                Log::warning('Syncing an AutoMod rule failed', ['guild' => $guild->id, 'rule' => $key, 'status' => $e->response->status(), 'body' => $e->response->json()]);
                $failed[$key] = $e->response->status() === 403 ? 'permission' : 'other';
                if ($rule === null) {
                    $guild->{$column} = null;
                }
            } catch (DomainException $e) {
                $failed[$key] = $e->getMessage();
            }
        }

        if ($existing !== null) {
            $keep = array_filter(array_map(fn (string $column) => $guild->{$column}, self::RULES));
            foreach ($this->leftovers($existing, $keep) as $rule) {
                rescue(fn () => $this->discord->deleteAutoModRule($guild->id, (string) $rule['id']), report: false);
            }
        }

        $guild->save();
        Cache::forget($this->cacheKey($guild->id));

        return $failed;
    }

    public function removeAll(Guild $guild): void
    {
        $existing = rescue(fn () => $this->discord->autoModRules($guild->id), [], report: false);

        foreach ($this->leftovers($existing, []) as $rule) {
            rescue(fn () => $this->discord->deleteAutoModRule($guild->id, (string) $rule['id']), report: false);
        }

        foreach (self::RULES as $key => $column) {
            $ids = array_filter([$guild->{$column}, ...array_column($this->own($existing, $key), 'id')]);
            foreach (array_unique($ids) as $id) {
                rescue(fn () => $this->discord->deleteAutoModRule($guild->id, (string) $id), report: false);
            }
            $guild->{$column} = null;
        }

        if ($guild->exists) {
            $guild->save();
        }
        Cache::forget($this->cacheKey($guild->id));
    }

    public function conflicts(Guild $guild): array
    {
        $existing = Cache::remember($this->cacheKey($guild->id), 30, fn () => rescue(fn () => $this->discord->autoModRules($guild->id), [], report: false));

        $conflicts = [];
        foreach (self::RULES as $key => $column) {
            if (self::TRIGGERS[$key] === 1) {
                continue;
            }
            $ours = array_column($this->own($existing, $key), 'id');
            foreach ($existing as $rule) {
                if ((int) ($rule['trigger_type'] ?? 0) === self::TRIGGERS[$key]
                    && ! in_array($rule['id'] ?? null, $ours, true)
                    && (string) ($rule['id'] ?? '') !== (string) $guild->{$column}) {
                    $conflicts[$key] = ['id' => (string) $rule['id'], 'name' => (string) ($rule['name'] ?? '')];
                }
            }
        }

        return $conflicts;
    }

    public function adopt(Guild $guild, string $key): bool
    {
        if (! isset(self::RULES[$key]) || self::TRIGGERS[$key] === 1) {
            return false;
        }

        $rule = collect($this->discord->autoModRules($guild->id))
            ->first(fn (array $rule) => (int) ($rule['trigger_type'] ?? 0) === self::TRIGGERS[$key]);
        if ($rule === null) {
            return false;
        }

        $metadata = $rule['trigger_metadata'] ?? [];
        $timeout = collect($rule['actions'] ?? [])->firstWhere('type', 3)['metadata']['duration_seconds'] ?? 0;

        match ($key) {
            'mentions' => $guild->fill([
                'automod_mentions' => min(self::MAX_MENTIONS, max(self::MIN_MENTIONS, (int) ($metadata['mention_total_limit'] ?? 5))),
                'automod_mention_raid' => (bool) ($metadata['mention_raid_protection_enabled'] ?? true),
                'automod_mentions_timeout' => $this->closestTimeout((int) $timeout),
            ]),
            'spam' => $guild->fill(['automod_spam' => true]),
            'presets' => $guild->fill([
                'automod_presets' => implode(',', array_intersect(self::PRESETS, array_map('intval', $metadata['presets'] ?? []))) ?: null,
                'automod_presets_allow' => implode("\n", $metadata['allow_list'] ?? []) ?: null,
            ]),
            'profile' => $guild->fill(['automod_profile_words' => implode("\n", $metadata['keyword_filter'] ?? []) ?: null]),
        };

        $guild->{self::RULES[$key]} = (string) $rule['id'];
        $guild->save();

        return true;
    }

    private function closestTimeout(int $seconds): int
    {
        return collect(self::TIMEOUTS)->sortBy(fn (int $option) => abs($option - $seconds))->first();
    }

    private function cacheKey(string $guildId): string
    {
        return "automod:rules:{$guildId}";
    }

    private function own(array $existing, string $key): array
    {
        $botId = (string) config('services.discord.client_id');
        $trigger = self::TRIGGERS[$key];
        $names = collect(array_keys(config('app.locales')))->map(fn (string $locale) => __('automod.rule_'.$key, [], $locale))->all();

        return array_values(array_filter($existing, fn (array $rule) => (string) ($rule['creator_id'] ?? '') === $botId
            && (int) ($rule['trigger_type'] ?? 0) === $trigger
            && ($trigger !== 1 || in_array($rule['name'] ?? '', $names, true))));
    }

    private function leftovers(array $existing, array $keep): array
    {
        $botId = (string) config('services.discord.client_id');
        $ours = array_merge(...array_map(fn (string $key) => array_column($this->own($existing, $key), 'id'), array_keys(self::RULES)));

        return array_values(array_filter($existing, fn (array $rule) => (string) ($rule['creator_id'] ?? '') === $botId
            && (int) ($rule['trigger_type'] ?? 0) === 1
            && str_starts_with((string) ($rule['name'] ?? ''), 'Toxbot')
            && ! in_array((string) ($rule['id'] ?? ''), $keep, true)
            && ! in_array($rule['id'] ?? null, $ours, true)));
    }

    private function apply(string $guildId, string $key, ?string $ruleId, ?array $rule, array $existing): ?string
    {
        $ids = array_column($this->own($existing, $key), 'id');
        if ($ruleId && in_array($ruleId, array_column($existing, 'id'), true) && ! in_array($ruleId, $ids, true)) {
            $ids[] = $ruleId;
        }
        $ruleId = $ruleId && in_array($ruleId, $ids, true) ? $ruleId : ($ids[0] ?? null);

        foreach ($ids as $id) {
            if ($id !== $ruleId) {
                $this->discord->deleteAutoModRule($guildId, (string) $id);
            }
        }

        if ($rule === null) {
            if ($ruleId) {
                $this->discord->deleteAutoModRule($guildId, $ruleId);
            }

            return null;
        }

        if ($ruleId && $this->discord->updateAutoModRule($guildId, $ruleId, array_diff_key($rule, ['trigger_type' => true, 'event_type' => true]))) {
            return $ruleId;
        }

        $others = array_filter($existing, fn (array $other) => (int) ($other['trigger_type'] ?? 0) === $rule['trigger_type'] && ! in_array($other['id'] ?? null, $ids, true));
        if ($rule['trigger_type'] !== 1 && $others !== []) {
            throw new DomainException('taken');
        }
        if ($rule['trigger_type'] === 1 && count($others) >= self::KEYWORD_RULES_PER_SERVER) {
            throw new DomainException('keyword_limit');
        }

        return $this->discord->createAutoModRule($guildId, $rule);
    }

    private function desired(Guild $guild, string $key): ?array
    {
        $metadata = match ($key) {
            'domains' => ['keyword_filter' => array_map(
                fn (string $domain) => '*'.mb_substr($domain, 0, self::MAX_KEYWORD_LENGTH - 2).'*',
                array_slice(DomainList::entries((string) $guild->link_blocklist), 0, self::MAX_KEYWORDS),
            )],
            'words' => [
                'keyword_filter' => array_slice(self::words($guild->automod_words), 0, self::MAX_KEYWORDS),
                'allow_list' => array_slice(self::words($guild->automod_words_allow), 0, self::MAX_ALLOWED),
            ],
            'mentions' => $guild->automod_mentions ? [
                'mention_total_limit' => (int) $guild->automod_mentions,
                'mention_raid_protection_enabled' => (bool) $guild->automod_mention_raid,
            ] : null,
            'spam' => $guild->automod_spam ? [] : null,
            'presets' => ($presets = array_map('intval', self::ids($guild->automod_presets))) !== [] ? [
                'presets' => $presets,
                'allow_list' => array_slice(self::words($guild->automod_presets_allow), 0, self::MAX_PRESET_ALLOWED),
            ] : null,
            'profile' => ['keyword_filter' => array_slice(self::words($guild->automod_profile_words), 0, self::MAX_KEYWORDS)],
        };

        if ($metadata === null || (array_key_exists('keyword_filter', $metadata) && $metadata['keyword_filter'] === [])) {
            return null;
        }

        $exemptRoles = $key === 'domains' ? $guild->link_exempt_roles : $guild->automod_exempt_roles;

        return [
            'name' => __('automod.rule_'.$key, [], $guild->messageLocale()),
            'event_type' => $key === 'profile' ? 2 : 1,
            'trigger_type' => self::TRIGGERS[$key],
            'trigger_metadata' => (object) $metadata,
            'actions' => $this->actions($guild, $key),
            'enabled' => true,
            'exempt_roles' => array_slice(self::ids($exemptRoles), 0, self::MAX_EXEMPT_ROLES),
            'exempt_channels' => $key === 'profile' ? [] : array_slice(self::ids($guild->automod_exempt_channels), 0, self::MAX_EXEMPT_CHANNELS),
        ];
    }

    private function actions(Guild $guild, string $key): array
    {
        if ($key === 'profile') {
            $actions = [['type' => 4]];
        } else {
            $message = trim((string) $guild->automod_message) ?: __('automod.blocked_'.$key, [], $guild->messageLocale());
            $actions = [['type' => 1, 'metadata' => ['custom_message' => mb_substr($message, 0, self::MAX_MESSAGE)]]];
        }

        if ($guild->modlog_channel) {
            $actions[] = ['type' => 2, 'metadata' => ['channel_id' => $guild->modlog_channel]];
        }

        $timeout = match ($key) {
            'words' => (int) $guild->automod_words_timeout,
            'mentions' => (int) $guild->automod_mentions_timeout,
            'domains' => $guild->link_action === 'timeout' ? 600 : 0,
            default => 0,
        };
        if ($timeout > 0) {
            $actions[] = ['type' => 3, 'metadata' => ['duration_seconds' => $timeout]];
        }

        return $actions;
    }
}
