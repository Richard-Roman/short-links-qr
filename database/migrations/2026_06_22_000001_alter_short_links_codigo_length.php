<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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

        if (! $this->codigoNeedsWiden($shortLinksTable)) {
            return;
        }

        Schema::table($shortLinksTable, function (Blueprint $table): void {
            $table->string('codigo', 64)->change();
        });
    }

    public function down(): void
    {
        $shortLinksTable = config('short-links.tables.short_links', 'short_links');

        if (! Schema::hasTable($shortLinksTable)) {
            return;
        }

        if (! $this->codigoNeedsNarrow($shortLinksTable)) {
            return;
        }

        Schema::table($shortLinksTable, function (Blueprint $table): void {
            $table->string('codigo', 10)->change();
        });
    }

    private function codigoNeedsWiden(string $shortLinksTable): bool
    {
        $maxLen = $this->codigoMaxLength($shortLinksTable);

        if ($maxLen === null) {
            return false;
        }

        return $maxLen < 64;
    }

    private function codigoNeedsNarrow(string $shortLinksTable): bool
    {
        $maxLen = $this->codigoMaxLength($shortLinksTable);

        if ($maxLen === null) {
            return false;
        }

        return $maxLen >= 64;
    }

    private function codigoMaxLength(string $shortLinksTable): ?int
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            $row = DB::selectOne(
                'SELECT character_maximum_length AS max_len
                 FROM information_schema.columns
                 WHERE table_schema = current_schema()
                   AND table_name = ?
                   AND column_name = ?',
                [$shortLinksTable, 'codigo'],
            );

            return $row === null ? null : (int) $row->max_len;
        }

        if ($driver === 'mysql') {
            $row = DB::selectOne(
                'SELECT CHARACTER_MAXIMUM_LENGTH AS max_len
                 FROM information_schema.columns
                 WHERE table_schema = DATABASE()
                   AND table_name = ?
                   AND column_name = ?',
                [$shortLinksTable, 'codigo'],
            );

            return $row === null ? null : (int) $row->max_len;
        }

        if ($driver === 'sqlite') {
            $quotedTable = '"' . str_replace('"', '""', $shortLinksTable) . '"';
            $rows = DB::select("PRAGMA table_info({$quotedTable})");

            foreach ($rows as $row) {
                if (($row->name ?? null) !== 'codigo') {
                    continue;
                }

                $type = (string) ($row->type ?? '');

                if (preg_match('/\((\d+)\)/', $type, $matches) === 1) {
                    return (int) $matches[1];
                }

                // Instalaciones nuevas vía create migration ya usan longitud 64 implícita.
                return 64;
            }

            return null;
        }

        return null;
    }
};
