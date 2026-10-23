<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COLUMNS = [
        'ticket_category', 'ticket_log_channel', 'ticket_limit',
        'ticket_panel_channel', 'ticket_panel_message_id', 'unban_enabled',
    ];

    public function up(): void
    {
        Schema::table('guilds', function (Blueprint $table) {
            $add = fn (string $column, callable $define) => Schema::hasColumn('guilds', $column) ? null : $define();

            $add('ticket_category', fn () => $table->string('ticket_category', 50)->nullable());
            $add('ticket_log_channel', fn () => $table->string('ticket_log_channel', 50)->nullable());
            $add('ticket_limit', fn () => $table->unsignedTinyInteger('ticket_limit')->default(1));
            $add('ticket_panel_channel', fn () => $table->string('ticket_panel_channel', 50)->nullable());
            $add('ticket_panel_message_id', fn () => $table->string('ticket_panel_message_id', 50)->nullable());
            $add('unban_enabled', fn () => $table->boolean('unban_enabled')->default(false));
        });

        if (! Schema::hasTable('ticket_types')) {
            Schema::create('ticket_types', function (Blueprint $table) {
                $table->id();
                $table->string('guild', 50)->index();
                $table->string('name', 80);
                $table->string('emoji', 50)->nullable();
                $table->text('description')->nullable();
                $table->text('roles')->nullable();
                $table->unsignedSmallInteger('position')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('tickets')) {
            Schema::create('tickets', function (Blueprint $table) {
                $table->id();
                $table->string('guild', 50);
                $table->unsignedInteger('number');
                $table->unsignedBigInteger('type_id')->nullable();
                $table->string('type_name', 80)->nullable();
                $table->string('user_id', 50);
                $table->string('user_name', 100)->nullable();
                $table->string('channel_id', 50)->nullable()->index();
                $table->string('status', 16)->default('open');
                $table->string('closed_by', 50)->nullable();
                $table->string('closed_by_name', 100)->nullable();
                $table->text('close_reason')->nullable();
                $table->dateTime('closed_at')->nullable();
                $table->timestamps();
                $table->unique(['guild', 'number']);
                $table->index(['guild', 'user_id', 'status']);
            });
        }

        if (! Schema::hasTable('ticket_messages')) {
            Schema::create('ticket_messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ticket_id')->index();
                $table->string('author_id', 50);
                $table->string('author_name', 100);
                $table->string('author_avatar', 255)->nullable();
                $table->boolean('author_bot')->default(false);
                $table->text('content')->nullable();
                $table->text('attachments')->nullable();
                $table->dateTime('sent_at');
            });
        }

        if (! Schema::hasTable('unban_appeals')) {
            Schema::create('unban_appeals', function (Blueprint $table) {
                $table->id();
                $table->string('guild', 50);
                $table->string('user_id', 50);
                $table->string('user_name', 100)->nullable();
                $table->text('reason');
                $table->string('status', 16)->default('pending');
                $table->string('handled_by', 50)->nullable();
                $table->string('handled_by_name', 100)->nullable();
                $table->text('response')->nullable();
                $table->dateTime('handled_at')->nullable();
                $table->timestamps();
                $table->index(['guild', 'status']);
                $table->index(['guild', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('unban_appeals');
        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('ticket_types');

        Schema::table('guilds', function (Blueprint $table) {
            foreach (self::COLUMNS as $column) {
                if (Schema::hasColumn('guilds', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
