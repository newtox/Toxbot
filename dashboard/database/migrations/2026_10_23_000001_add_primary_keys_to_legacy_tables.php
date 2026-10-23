<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = ['users', 'guilds'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table) || $this->hasPrimaryKey($table)) {
                continue;
            }

            DB::transaction(fn () => $this->mergeDuplicates($table));

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->string('id', 50)->change();
                $blueprint->primary('id');
            });
        }
    }

    public function down(): void {}

    private function hasPrimaryKey(string $table): bool
    {
        return collect(Schema::getIndexes($table))->contains(fn (array $index) => $index['primary']);
    }

    private function mergeDuplicates(string $table): void
    {
        $columns = array_values(array_diff(Schema::getColumnListing($table), ['id']));
        $grammar = DB::getQueryGrammar();
        $select = collect($columns)
            ->map(fn (string $column) => 'MAX('.$grammar->wrap($column).') AS '.$grammar->wrap($column))
            ->implode(', ');

        $ids = DB::table($table)->select('id')->groupBy('id')->havingRaw('COUNT(*) > 1')->pluck('id');

        foreach ($ids as $id) {
            $merged = (array) DB::table($table)->where('id', $id)->selectRaw($select)->first();

            DB::table($table)->where('id', $id)->delete();
            DB::table($table)->insert(['id' => $id] + $merged);
        }
    }
};
