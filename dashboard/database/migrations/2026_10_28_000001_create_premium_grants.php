<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('premium_grants')) {
            Schema::create('premium_grants', function (Blueprint $table) {
                $table->id();
                $table->string('type', 10);
                $table->string('target_id', 50);
                $table->dateTime('ends_at')->nullable();
                $table->string('note', 200)->nullable();
                $table->string('granted_by', 50)->nullable();
                $table->timestamps();
                $table->unique(['type', 'target_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('premium_grants');
    }
};
