<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('guild_events')) {
            return;
        }

        Schema::create('guild_events', function (Blueprint $table) {
            $table->id();
            $table->string('guild', 32)->index();
            $table->string('name', 100);
            $table->string('role_id', 32)->nullable();
            $table->string('category_id', 32)->nullable();
            $table->string('text_channel_id', 32)->nullable();
            $table->string('voice_channel_id', 32)->nullable();
            $table->string('created_by', 32);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guild_events');
    }
};
