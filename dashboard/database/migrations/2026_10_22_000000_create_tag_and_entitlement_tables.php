<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tags')) {
            Schema::create('tags', function (Blueprint $table) {
                $table->id();
                $table->string('name', 32)->unique();
                $table->string('owner', 50)->index();
                $table->text('content');
                $table->boolean('embed')->default(false);
                $table->string('embed_color', 7)->nullable();
                $table->string('embed_image', 512)->nullable();
                $table->unsignedInteger('uses')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('tag_guild_blocks')) {
            Schema::create('tag_guild_blocks', function (Blueprint $table) {
                $table->id();
                $table->string('guild', 50);
                $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
                $table->unique(['guild', 'tag_id']);
            });
        }

        if (! Schema::hasTable('tag_reports')) {
            Schema::create('tag_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
                $table->string('reporter', 50);
                $table->string('guild', 50)->nullable();
                $table->string('reason', 500);
                $table->boolean('resolved')->default(false)->index();
                $table->timestamps();
                $table->unique(['tag_id', 'reporter']);
            });
        }

        if (! Schema::hasTable('entitlements')) {
            Schema::create('entitlements', function (Blueprint $table) {
                $table->string('id', 50)->primary();
                $table->string('sku_id', 50)->index();
                $table->string('user_id', 50)->nullable()->index();
                $table->string('guild_id', 50)->nullable()->index();
                $table->unsignedTinyInteger('type');
                $table->dateTime('starts_at')->nullable();
                $table->dateTime('ends_at')->nullable();
                $table->boolean('deleted')->default(false);
                $table->boolean('consumed')->default(false);
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (! Schema::hasColumn('guilds', 'tags_enabled')) {
            Schema::table('guilds', function (Blueprint $table) {
                $table->boolean('tags_enabled')->default(true);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('guilds', 'tags_enabled')) {
            Schema::table('guilds', function (Blueprint $table) {
                $table->dropColumn('tags_enabled');
            });
        }

        Schema::dropIfExists('tag_reports');
        Schema::dropIfExists('tag_guild_blocks');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('entitlements');
    }
};
