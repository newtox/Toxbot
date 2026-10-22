<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('premium_servers')) {
            Schema::create('premium_servers', function (Blueprint $table) {
                $table->string('user_id', 50)->primary();
                $table->string('guild_id', 50)->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('premium_servers');
    }
};
