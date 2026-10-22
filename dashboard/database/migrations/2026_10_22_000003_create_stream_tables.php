<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('stream_sources')) {
            Schema::create('stream_sources', function (Blueprint $table) {
                $table->id();
                $table->string('platform', 16);
                $table->string('external_id', 64);
                $table->string('login', 100)->nullable();
                $table->string('name', 100);
                $table->string('avatar_url', 512)->nullable();
                $table->string('twitch_online_sub', 64)->nullable();
                $table->string('twitch_offline_sub', 64)->nullable();
                $table->dateTime('websub_expires_at')->nullable();
                $table->string('live_item', 64)->nullable();
                $table->dateTime('live_since')->nullable();
                $table->timestamps();
                $table->unique(['platform', 'external_id']);
            });
        }

        if (! Schema::hasTable('stream_subscriptions')) {
            Schema::create('stream_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->string('guild', 50)->index();
                $table->foreignId('stream_source_id')->constrained('stream_sources')->cascadeOnDelete();
                $table->text('message')->nullable();
                $table->string('role', 50)->nullable();
                $table->string('filter_games', 500)->nullable();
                $table->string('filter_title', 200)->nullable();
                $table->string('end_action', 16)->default('keep');
                $table->boolean('notify_videos')->default(true);
                $table->boolean('notify_live')->default(true);
                $table->timestamps();
                $table->unique(['guild', 'stream_source_id']);
            });
        }

        if (! Schema::hasTable('stream_announcements')) {
            Schema::create('stream_announcements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('stream_subscription_id')->constrained('stream_subscriptions')->cascadeOnDelete();
                $table->string('item_id', 64);
                $table->string('kind', 16);
                $table->string('channel_id', 50)->nullable();
                $table->string('message_id', 50)->nullable();
                $table->dateTime('started_at')->nullable();
                $table->dateTime('ended_at')->nullable();
                $table->timestamps();
                $table->unique(['stream_subscription_id', 'item_id']);
                $table->index(['kind', 'ended_at']);
            });
        }

        if (! Schema::hasTable('webhook_deliveries')) {
            Schema::create('webhook_deliveries', function (Blueprint $table) {
                $table->string('id', 100)->primary();
                $table->timestamp('created_at')->nullable()->index();
            });
        }

        Schema::table('guilds', function (Blueprint $table) {
            if (! Schema::hasColumn('guilds', 'stream_msg')) {
                $table->text('stream_msg')->nullable();
            }
            if (! Schema::hasColumn('guilds', 'live_role')) {
                $table->string('live_role', 50)->nullable();
            }
            if (! Schema::hasColumn('guilds', 'live_role_required')) {
                $table->string('live_role_required', 50)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('guilds', function (Blueprint $table) {
            foreach (['stream_msg', 'live_role', 'live_role_required'] as $column) {
                if (Schema::hasColumn('guilds', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('stream_announcements');
        Schema::dropIfExists('stream_subscriptions');
        Schema::dropIfExists('stream_sources');
    }
};
