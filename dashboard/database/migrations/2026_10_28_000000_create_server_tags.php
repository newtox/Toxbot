<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('server_tags')) {
            Schema::create('server_tags', function (Blueprint $table) {
                $table->id();
                $table->string('guild', 50);
                $table->string('name', 32);
                $table->text('content');
                $table->unsignedInteger('uses')->default(0);
                $table->string('created_by', 50)->nullable();
                $table->timestamps();
                $table->unique(['guild', 'name']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('server_tags');
    }
};
