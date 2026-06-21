<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $shortLinksTable = config('short-links.tables.short_links', 'short_links');

        if (! Schema::hasTable($shortLinksTable)) {
            return;
        }

        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        if (! $this->codigoNeedsWiden($shortLinksTable)) {
            return;
        }

        DB::statement("ALTER TABLE {$shortLinksTable} ALTER COLUMN codigo TYPE varchar(64)");
    }

    public function down(): void
    {
        $shortLinksTable = config('short-links.tables.short_links', 'short_links');

        if (! Schema::hasTable($shortLinksTable)) {
            return;
        }

        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("ALTER TABLE {$shortLinksTable} ALTER COLUMN codigo TYPE varchar(10)");
    }

    private function codigoNeedsWiden(string $shortLinksTable): bool
    {
        $row = DB::selectOne(
            'SELECT character_maximum_length AS max_len
             FROM information_schema.columns
             WHERE table_schema = current_schema()
               AND table_name = ?
               AND column_name = ?',
            [$shortLinksTable, 'codigo'],
        );

        if ($row === null) {
            return false;
        }

        return (int) $row->max_len < 64;
    }
};
