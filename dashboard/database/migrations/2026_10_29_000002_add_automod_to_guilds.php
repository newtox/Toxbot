<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COLUMNS = ['automod_words', 'automod_mentions', 'automod_exempt_roles', 'automod_rule_domains', 'automod_rule_mentions', 'automod_rule_words'];

    public function up(): void
    {
        Schema::table('guilds', function (Blueprint $table) {
            if (! Schema::hasColumn('guilds', 'automod_words')) {
                $table->text('automod_words')->nullable();
            }
            if (! Schema::hasColumn('guilds', 'automod_mentions')) {
                $table->unsignedTinyInteger('automod_mentions')->nullable();
            }
            if (! Schema::hasColumn('guilds', 'automod_exempt_roles')) {
                $table->text('automod_exempt_roles')->nullable();
            }
            foreach (['automod_rule_domains', 'automod_rule_mentions', 'automod_rule_words'] as $column) {
                if (! Schema::hasColumn('guilds', $column)) {
                    $table->string($column, 32)->nullable();
                }
            }
        });
    }

    public function down(): void
    {
        foreach (self::COLUMNS as $column) {
            if (Schema::hasColumn('guilds', $column)) {
                Schema::table('guilds', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }
};
