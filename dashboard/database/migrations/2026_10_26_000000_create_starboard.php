<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COLUMNS = ['starboard_channel', 'starboard_emoji', 'starboard_threshold'];

    public function up(): void
    {
        Schema::table('guilds', function (Blueprint $table) {
            $add = fn (string $column, callable $define) => Schema::hasColumn('guilds', $column) ? null : $define();

            $add('starboard_channel', fn () => $table->string('starboard_channel', 50)->nullable());
            $add('starboard_emoji', fn () => $table->string('starboard_emoji', 80)->nullable());
            $add('starboard_threshold', fn () => $table->unsignedTinyInteger('starboard_threshold')->default(3));
        });

        if (! Schema::hasTable('starboard_posts')) {
            Schema::create('starboard_posts', function (Blueprint $table) {
                $table->id();
                $table->string('guild', 50);
                $table->string('channel_id', 50);
                $table->string('message_id', 50)->unique();
                $table->string('author_id', 50);
                $table->string('author_name', 100)->nullable();
                $table->string('author_avatar', 255)->nullable();
                $table->text('content')->nullable();
                $table->string('image_url', 500)->nullable();
                $table->unsignedInteger('stars')->default(0);
                $table->string('starboard_channel_id', 50)->nullable();
                $table->string('starboard_message_id', 50)->nullable();
                $table->dateTime('message_created_at')->nullable();
                $table->timestamps();
                $table->index(['guild', 'stars']);
                $table->index(['guild', 'created_at']);
                $table->index('author_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('starboard_posts');

        Schema::table('guilds', function (Blueprint $table) {
            foreach (self::COLUMNS as $column) {
                if (Schema::hasColumn('guilds', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
