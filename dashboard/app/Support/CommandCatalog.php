<?php

namespace App\Support;

class CommandCatalog
{
    public const HIDDEN = ['system'];

    private const CHAT_INPUT = 1;

    private const SUB_COMMAND = 1;

    private const SUB_COMMAND_GROUP = 2;

    public static function build(array $commands, string $locale): array
    {
        return collect($commands)
            ->filter(fn (array $command) => ($command['type'] ?? self::CHAT_INPUT) === self::CHAT_INPUT)
            ->reject(fn (array $command) => in_array($command['name'], self::HIDDEN, true))
            ->map(fn (array $command) => [
                'name' => $command['name'],
                'description' => self::text($command, 'description', $locale),
                'restricted' => ($command['default_member_permissions'] ?? null) !== null,
                'entries' => self::entries($command, '/'.$command['name'], $locale),
            ])
            ->sortBy('name')
            ->values()
            ->all();
    }

    public static function search(array $catalog, string $query): array
    {
        $query = mb_strtolower(trim(ltrim(trim($query), '/')));
        if ($query === '') {
            return $catalog;
        }

        return array_values(array_filter($catalog, function (array $command) use ($query) {
            $haystack = [$command['name'], $command['description']];
            foreach ($command['entries'] as $entry) {
                $haystack[] = $entry['path'];
                $haystack[] = $entry['description'];
            }

            return str_contains(mb_strtolower(implode("\n", $haystack)), $query);
        }));
    }

    private static function entries(array $option, string $path, string $locale): array
    {
        $children = $option['options'] ?? [];
        $subs = array_filter($children, fn (array $o) => in_array($o['type'], [self::SUB_COMMAND, self::SUB_COMMAND_GROUP], true));

        if ($subs === []) {
            return [[
                'path' => $path,
                'description' => self::text($option, 'description', $locale),
                'options' => array_values(array_map(fn (array $o) => [
                    'name' => $o['name'],
                    'type' => $o['type'],
                    'required' => (bool) ($o['required'] ?? false),
                    'description' => self::text($o, 'description', $locale),
                ], $children)),
            ]];
        }

        $entries = [];
        foreach ($subs as $sub) {
            array_push($entries, ...self::entries($sub, $path.' '.$sub['name'], $locale));
        }

        return $entries;
    }

    private static function text(array $item, string $key, string $locale): string
    {
        return (string) ($item[$key.'_localizations'][$locale] ?? $item[$key] ?? '');
    }
}
