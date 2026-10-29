<?php

namespace App\Services;

use App\Models\Guild;
use App\Support\DomainList;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

class AutoMod
{
    public const MAX_KEYWORD_LENGTH = 60;

    public const MAX_KEYWORDS = 1000;

    public const MAX_EXEMPT_ROLES = 20;

    public const MIN_MENTIONS = 3;

    public const MAX_MENTIONS = 50;

    private const RULES = [
        'domains' => 'automod_rule_domains',
        'mentions' => 'automod_rule_mentions',
        'words' => 'automod_rule_words',
    ];

    public function __construct(private readonly Discord $discord) {}

    public static function words(?string $input): array
    {
        $words = preg_split('/[\r\n,]+/u', (string) $input) ?: [];

        return array_values(array_unique(array_filter(array_map(fn ($word) => mb_strtolower(trim($word)), $words), fn ($word) => $word !== '')));
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
                $existing ??= $this->existingRules($guild->id);
                $guild->{$column} = $this->apply($guild->id, $key, $guild->{$column}, $rule, $existing);
            } catch (RequestException $e) {
                Log::warning('Syncing an AutoMod rule failed', ['guild' => $guild->id, 'rule' => $key, 'status' => $e->response->status(), 'body' => $e->response->json()]);
                $failed[$key] = $e->response->status() === 403 ? 'permission' : 'failed';
                if ($rule === null) {
                    $guild->{$column} = null;
                }
            } catch (\DomainException $e) {
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

        return $failed;
    }

    public function removeAll(Guild $guild): void
    {
        $existing = rescue(fn () => $this->existingRules($guild->id), [], report: false);

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
    }

    private function existingRules(string $guildId): array
    {
        return $this->discord->autoModRules($guildId);
    }

    private function own(array $existing, string $key): array
    {
        $botId = (string) config('services.discord.client_id');
        $trigger = $key === 'mentions' ? 5 : 1;
        $names = collect(array_keys(config('app.locales')))->map(fn (string $locale) => __('automod.rule_'.$key, [], $locale))->all();

        return array_values(array_filter($existing, fn (array $rule) => (string) ($rule['creator_id'] ?? '') === $botId
            && (int) ($rule['trigger_type'] ?? 0) === $trigger
            && ($trigger === 5 || in_array($rule['name'] ?? '', $names, true))));
    }

    private function leftovers(array $existing, array $keep): array
    {
        $botId = (string) config('services.discord.client_id');

        return array_values(array_filter($existing, fn (array $rule) => (string) ($rule['creator_id'] ?? '') === $botId
            && (int) ($rule['trigger_type'] ?? 0) === 1
            && str_starts_with((string) ($rule['name'] ?? ''), 'Toxbot')
            && ! in_array((string) ($rule['id'] ?? ''), $keep, true)
            && ! in_array((string) ($rule['id'] ?? ''), array_merge(...array_map(fn (string $key) => array_column($this->own($existing, $key), 'id'), array_keys(self::RULES))), true)));
    }

    private function apply(string $guildId, string $key, ?string $ruleId, ?array $rule, array $existing): ?string
    {
        $own = $this->own($existing, $key);
        $ids = array_column($own, 'id');
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

        if ($ruleId && $this->discord->updateAutoModRule($guildId, $ruleId, array_diff_key($rule, ['trigger_type' => true]))) {
            return $ruleId;
        }

        $others = array_filter($existing, fn (array $other) => (int) ($other['trigger_type'] ?? 0) === $rule['trigger_type'] && ! in_array($other['id'] ?? null, $ids, true));
        if ($rule['trigger_type'] === 5 && $others !== []) {
            throw new \DomainException('mention_taken');
        }
        if ($rule['trigger_type'] === 1 && count($others) >= 6) {
            throw new \DomainException('keyword_limit');
        }

        return $this->discord->createAutoModRule($guildId, $rule + ['event_type' => 1]);
    }

    private function desired(Guild $guild, string $key): ?array
    {
        $locale = $guild->messageLocale();

        [$trigger, $metadata, $exempt] = match ($key) {
            'domains' => [1, ['keyword_filter' => array_map(
                fn (string $domain) => '*'.mb_substr($domain, 0, self::MAX_KEYWORD_LENGTH - 2).'*',
                array_slice(DomainList::entries((string) $guild->link_blocklist), 0, self::MAX_KEYWORDS),
            )], $guild->link_exempt_roles],
            'mentions' => [5, $guild->automod_mentions ? [
                'mention_total_limit' => $guild->automod_mentions,
                'mention_raid_protection_enabled' => true,
            ] : null, $guild->automod_exempt_roles],
            'words' => [1, ['keyword_filter' => array_slice(self::words($guild->automod_words), 0, self::MAX_KEYWORDS)], $guild->automod_exempt_roles],
        };

        if ($metadata === null || (isset($metadata['keyword_filter']) && $metadata['keyword_filter'] === [])) {
            return null;
        }

        $actions = [['type' => 1, 'metadata' => ['custom_message' => mb_substr(__('automod.blocked_'.$key, [], $locale), 0, 150)]]];
        if ($guild->modlog_channel) {
            $actions[] = ['type' => 2, 'metadata' => ['channel_id' => $guild->modlog_channel]];
        }

        return [
            'name' => __('automod.rule_'.$key, [], $locale),
            'trigger_type' => $trigger,
            'trigger_metadata' => $metadata,
            'actions' => $actions,
            'enabled' => true,
            'exempt_roles' => array_slice(array_values(array_filter(explode(',', (string) $exempt))), 0, self::MAX_EXEMPT_ROLES),
        ];
    }
}
