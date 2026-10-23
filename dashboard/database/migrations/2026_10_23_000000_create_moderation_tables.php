<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('mod_cases')) {
            Schema::create('mod_cases', function (Blueprint $table) {
                $table->id();
                $table->string('guild', 50);
                $table->unsignedInteger('number');
                $table->string('type', 16);
                $table->string('user_id', 50);
                $table->string('user_name', 100)->nullable();
                $table->string('moderator_id', 50)->nullable();
                $table->string('moderator_name', 100)->nullable();
                $table->text('reason')->nullable();
                $table->unsignedInteger('duration')->nullable();
                $table->dateTime('expires_at')->nullable();
                $table->boolean('active')->default(true);
                $table->string('source', 16)->default('command');
                $table->string('log_message_id', 50)->nullable();
                $table->timestamps();
                $table->unique(['guild', 'number']);
                $table->index(['guild', 'user_id']);
                $table->index(['type', 'active', 'expires_at']);
            });
        }

        if (! Schema::hasTable('mod_actions')) {
            Schema::create('mod_actions', function (Blueprint $table) {
                $table->id();
                $table->string('guild', 50);
                $table->string('type', 16);
                $table->string('user_id', 50);
                $table->string('moderator_id', 50);
                $table->string('moderator_name', 100)->nullable();
                $table->text('reason')->nullable();
                $table->unsignedInteger('duration')->nullable();
                $table->unsignedTinyInteger('delete_days')->default(0);
                $table->string('status', 16)->default('pending');
                $table->string('error', 100)->nullable();
                $table->unsignedBigInteger('case_id')->nullable();
                $table->timestamps();
                $table->index(['status', 'created_at']);
            });
        }

        if (! Schema::hasTable('warn_rules')) {
            Schema::create('warn_rules', function (Blueprint $table) {
                $table->id();
                $table->string('guild', 50);
                $table->unsignedTinyInteger('warns');
                $table->string('action', 16);
                $table->unsignedInteger('duration')->nullable();
                $table->timestamps();
                $table->unique(['guild', 'warns']);
            });
        }

        Schema::table('guilds', function (Blueprint $table) {
            if (! Schema::hasColumn('guilds', 'modlog_channel')) {
                $table->string('modlog_channel', 50)->nullable();
            }
            if (! Schema::hasColumn('guilds', 'mod_dm')) {
                $table->boolean('mod_dm')->default(true);
            }
        });
    }

    public function down(): void
    {
        Schema::table('guilds', function (Blueprint $table) {
            foreach (['modlog_channel', 'mod_dm'] as $column) {
                if (Schema::hasColumn('guilds', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('warn_rules');
        Schema::dropIfExists('mod_actions');
        Schema::dropIfExists('mod_cases');
    }
};
