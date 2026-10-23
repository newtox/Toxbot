<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COLUMNS = [
        'verify_enabled', 'verify_role', 'verify_channel', 'verify_message_id',
        'link_invites', 'link_scam', 'link_allowlist_enabled', 'link_allowlist', 'link_blocklist', 'link_action',
        'raid_enabled', 'raid_joins', 'raid_seconds', 'raid_minutes', 'raid_kick', 'raid_level',
        'raid_until', 'raid_previous_level', 'min_account_age',
    ];

    public function up(): void
    {
        Schema::table('guilds', function (Blueprint $table) {
            $add = fn (string $column, callable $define) => Schema::hasColumn('guilds', $column) ? null : $define();

            $add('verify_enabled', fn () => $table->boolean('verify_enabled')->default(false));
            $add('verify_role', fn () => $table->string('verify_role', 50)->nullable());
            $add('verify_channel', fn () => $table->string('verify_channel', 50)->nullable());
            $add('verify_message_id', fn () => $table->string('verify_message_id', 50)->nullable());

            $add('link_invites', fn () => $table->boolean('link_invites')->default(false));
            $add('link_scam', fn () => $table->boolean('link_scam')->default(false));
            $add('link_allowlist_enabled', fn () => $table->boolean('link_allowlist_enabled')->default(false));
            $add('link_allowlist', fn () => $table->text('link_allowlist')->nullable());
            $add('link_blocklist', fn () => $table->text('link_blocklist')->nullable());
            $add('link_action', fn () => $table->string('link_action', 16)->default('delete'));

            $add('raid_enabled', fn () => $table->boolean('raid_enabled')->default(false));
            $add('raid_joins', fn () => $table->unsignedSmallInteger('raid_joins')->default(10));
            $add('raid_seconds', fn () => $table->unsignedSmallInteger('raid_seconds')->default(10));
            $add('raid_minutes', fn () => $table->unsignedSmallInteger('raid_minutes')->default(10));
            $add('raid_kick', fn () => $table->boolean('raid_kick')->default(true));
            $add('raid_level', fn () => $table->boolean('raid_level')->default(true));
            $add('raid_until', fn () => $table->dateTime('raid_until')->nullable());
            $add('raid_previous_level', fn () => $table->unsignedTinyInteger('raid_previous_level')->nullable());
            $add('min_account_age', fn () => $table->unsignedSmallInteger('min_account_age')->default(0));
        });

        if (! Schema::hasTable('verifications')) {
            Schema::create('verifications', function (Blueprint $table) {
                $table->id();
                $table->string('guild', 50);
                $table->string('user_id', 50);
                $table->dateTime('created_at')->nullable();
                $table->dateTime('verified_at')->nullable();
                $table->unique(['guild', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('verifications');

        Schema::table('guilds', function (Blueprint $table) {
            foreach (self::COLUMNS as $column) {
                if (Schema::hasColumn('guilds', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
