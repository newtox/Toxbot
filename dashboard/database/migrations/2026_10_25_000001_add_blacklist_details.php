<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blacklist', function (Blueprint $table) {
            if (! Schema::hasColumn('blacklist', 'reason')) {
                $table->string('reason', 200)->nullable();
            }
            if (! Schema::hasColumn('blacklist', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('blacklist', function (Blueprint $table) {
            foreach (['reason', 'created_at'] as $column) {
                if (Schema::hasColumn('blacklist', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
