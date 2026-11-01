<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COLUMNS = [
        'automod_words_allow' => 'text',
        'automod_words_timeout' => 'int',
        'automod_mention_raid' => 'raid',
        'automod_mentions_timeout' => 'int',
        'automod_spam' => 'bool',
        'automod_presets' => 'short',
        'automod_presets_allow' => 'text',
        'automod_profile_words' => 'text',
        'automod_message' => 'message',
        'automod_exempt_channels' => 'text',
        'automod_rule_spam' => 'id',
        'automod_rule_presets' => 'id',
        'automod_rule_profile' => 'id',
    ];

    public function up(): void
    {
        foreach (self::COLUMNS as $column => $kind) {
            if (Schema::hasColumn('guilds', $column)) {
                continue;
            }
            Schema::table('guilds', function (Blueprint $table) use ($column, $kind) {
                match ($kind) {
                    'text' => $table->text($column)->nullable(),
                    'int' => $table->unsignedInteger($column)->nullable(),
                    'raid' => $table->boolean($column)->default(true),
                    'bool' => $table->boolean($column)->default(false),
                    'short' => $table->string($column, 16)->nullable(),
                    'message' => $table->string($column, 150)->nullable(),
                    'id' => $table->string($column, 32)->nullable(),
                };
            });
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::COLUMNS) as $column) {
            if (Schema::hasColumn('guilds', $column)) {
                Schema::table('guilds', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }
};
