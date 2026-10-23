<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ScamList
{
    public const SOURCES = [
        'DSP Scam Links' => [
            'page' => 'https://github.com/Discord-AntiScam/scam-links',
            'list' => 'https://raw.githubusercontent.com/Discord-AntiScam/scam-links/main/list.txt',
        ],
        'discord-phishing-links' => [
            'page' => 'https://github.com/nikolaischunk/discord-phishing-links',
            'list' => 'https://raw.githubusercontent.com/nikolaischunk/discord-phishing-links/main/txt/domain-list.txt',
        ],
    ];

    public function domains(): array
    {
        $cached = Cache::get('scam-list:domains');
        if (is_array($cached)) {
            return $cached;
        }

        $domains = [];
        foreach (self::SOURCES as $source) {
            $response = rescue(fn () => Http::timeout(15)->get($source['list']), null, report: false);
            if (! $response?->successful()) {
                continue;
            }
            foreach (preg_split('/\R/', $response->body()) as $line) {
                $domain = strtolower(trim($line));
                if ($domain !== '' && str_contains($domain, '.')) {
                    $domains[$domain] = true;
                }
            }
        }

        Cache::put('scam-list:domains', $domains, $domains === [] ? 300 : 21600);

        return $domains;
    }

    public function count(): int
    {
        return count($this->domains());
    }

    public function listed(string $host): ?string
    {
        $domains = $this->domains();
        $parts = explode('.', $host);

        for ($i = 0; $i < count($parts) - 1; $i++) {
            $candidate = implode('.', array_slice($parts, $i));
            if (isset($domains[$candidate])) {
                return $candidate;
            }
        }

        return null;
    }
}
