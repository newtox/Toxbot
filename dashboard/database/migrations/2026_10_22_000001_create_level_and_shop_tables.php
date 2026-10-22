<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const USER_COLUMNS = [
        'xp', 'credits', 'commands_used', 'xp_at', 'daily_at', 'daily_streak', 'background_id', 'xp_boost_until',
    ];

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'xp')) {
                $table->unsignedBigInteger('xp')->default(0)->index();
            }
            if (! Schema::hasColumn('users', 'credits')) {
                $table->bigInteger('credits')->default(0);
            }
            if (! Schema::hasColumn('users', 'commands_used')) {
                $table->unsignedInteger('commands_used')->default(0);
            }
            if (! Schema::hasColumn('users', 'xp_at')) {
                $table->dateTime('xp_at')->nullable();
            }
            if (! Schema::hasColumn('users', 'daily_at')) {
                $table->dateTime('daily_at')->nullable();
            }
            if (! Schema::hasColumn('users', 'daily_streak')) {
                $table->unsignedInteger('daily_streak')->default(0);
            }
            if (! Schema::hasColumn('users', 'background_id')) {
                $table->unsignedBigInteger('background_id')->nullable();
            }
            if (! Schema::hasColumn('users', 'xp_boost_until')) {
                $table->dateTime('xp_boost_until')->nullable();
            }
        });

        if (! Schema::hasTable('member_levels')) {
            Schema::create('member_levels', function (Blueprint $table) {
                $table->id();
                $table->string('guild', 50);
                $table->string('user', 50);
                $table->unsignedBigInteger('xp')->default(0);
                $table->dateTime('xp_at')->nullable();
                $table->unique(['guild', 'user']);
                $table->index(['guild', 'xp']);
            });
        }

        if (! Schema::hasTable('backgrounds')) {
            Schema::create('backgrounds', function (Blueprint $table) {
                $table->id();
                $table->string('name', 64);
                $table->string('file');
                $table->unsignedInteger('price')->default(0);
                $table->boolean('premium_only')->default(false);
                $table->boolean('active')->default(true);
                $table->integer('sort')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('user_backgrounds')) {
            Schema::create('user_backgrounds', function (Blueprint $table) {
                $table->id();
                $table->string('user', 50);
                $table->foreignId('background_id')->constrained('backgrounds')->cascadeOnDelete();
                $table->timestamp('created_at')->nullable();
                $table->unique(['user', 'background_id']);
            });
        }

        if (! Schema::hasTable('credit_transactions')) {
            Schema::create('credit_transactions', function (Blueprint $table) {
                $table->id();
                $table->string('user', 50)->index();
                $table->bigInteger('amount');
                $table->string('reason', 32);
                $table->string('reference', 100)->nullable()->unique();
                $table->timestamp('created_at')->nullable();
            });
        }

        Schema::table('guilds', function (Blueprint $table) {
            if (! Schema::hasColumn('guilds', 'levelup_enabled')) {
                $table->boolean('levelup_enabled')->default(false);
            }
            if (! Schema::hasColumn('guilds', 'levelup_channel')) {
                $table->string('levelup_channel', 50)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('guilds', function (Blueprint $table) {
            foreach (['levelup_enabled', 'levelup_channel'] as $column) {
                if (Schema::hasColumn('guilds', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('credit_transactions');
        Schema::dropIfExists('user_backgrounds');
        Schema::dropIfExists('backgrounds');
        Schema::dropIfExists('member_levels');

        Schema::table('users', function (Blueprint $table) {
            foreach (self::USER_COLUMNS as $column) {
                if (Schema::hasColumn('users', $column)) {
                    if ($column === 'xp') {
                        $table->dropIndex(['xp']);
                    }
                    $table->dropColumn($column);
                }
            }
        });
    }
};
