<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backgrounds', function (Blueprint $table) {
            if (! Schema::hasColumn('backgrounds', 'type')) {
                $table->string('type', 16)->default('background')->after('name');
            }
            if (! Schema::hasColumn('backgrounds', 'animated')) {
                $table->boolean('animated')->default(false)->after('file');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'frame_id')) {
                $table->unsignedBigInteger('frame_id')->nullable();
            }
            if (! Schema::hasColumn('users', 'bar_id')) {
                $table->unsignedBigInteger('bar_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['frame_id', 'bar_id'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('backgrounds', function (Blueprint $table) {
            foreach (['type', 'animated'] as $column) {
                if (Schema::hasColumn('backgrounds', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
