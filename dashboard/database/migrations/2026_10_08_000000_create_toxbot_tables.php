<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('guilds')) {
            Schema::create('guilds', function (Blueprint $table) {
                $table->string('id', 50)->primary();
                $table->string('welcome_channel', 50)->nullable();
                $table->string('bye_channel', 50)->nullable();
                $table->longText('welcome_msg')->nullable();
                $table->longText('bye_msg')->nullable();
                $table->string('autorole', 50)->nullable();
                $table->string('notify', 50)->nullable();
                $table->string('stream', 50)->nullable();
            });
        } else {
            Schema::table('guilds', function (Blueprint $table) {
                if (! Schema::hasColumn('guilds', 'notify')) {
                    $table->string('notify', 50)->nullable();
                }
                if (! Schema::hasColumn('guilds', 'stream')) {
                    $table->string('stream', 50)->nullable();
                }
            });
        }

        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->string('id', 50)->primary();
                $table->longText('language');
                $table->string('color', 50)->default('#7289da');
            });
        }

        if (! Schema::hasTable('blacklist')) {
            Schema::create('blacklist', function (Blueprint $table) {
                $table->id();
                $table->string('user', 50)->nullable()->index();
                $table->string('guild', 50)->nullable()->index();
            });
        }
    }

    public function down(): void
    {
    }
};
