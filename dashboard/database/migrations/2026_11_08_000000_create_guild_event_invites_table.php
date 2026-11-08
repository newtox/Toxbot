<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('guild_event_invites')) {
            return;
        }

        Schema::create('guild_event_invites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('guild_events')->cascadeOnDelete();
            $table->string('user_id', 32);
            $table->string('status', 16);
            $table->string('invited_by', 32)->nullable();
            $table->timestamps();
            $table->unique(['event_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guild_event_invites');
    }
};
