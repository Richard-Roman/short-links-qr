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
        $clicksTable = config('short-links.tables.short_link_clicks', 'short_link_clicks');

        if (Schema::hasTable($shortLinksTable) || Schema::hasTable($clicksTable)) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        Schema::create($shortLinksTable, function (Blueprint $table) use ($driver): void {
            $table->uuid('id')->primary();
            $table->string('codigo', 64)->unique();
            $table->text('url_destino');
            $table->string('entidad_tipo', 30)->nullable();
            $table->uuid('entidad_id')->nullable();
            $table->string('titulo', 200)->nullable();
            $table->uuid('creado_por')->nullable();
            $table->boolean('activo')->default(true);
            $table->integer('total_clicks')->default(0);
            $table->text('qr_storage_url')->nullable();
            $table->timestampTz('creado_en')->useCurrent();

            if ($driver === 'mysql') {
                $table->uuid('entidad_activa_id')
                    ->virtualAs('CASE WHEN activo = 1 THEN entidad_id ELSE NULL END')
                    ->nullable();
            }
        });

        Schema::create($clicksTable, function (Blueprint $table) use ($shortLinksTable): void {
            $table->id();
            $table->foreignUuid('short_link_id')->references('id')->on($shortLinksTable)->cascadeOnDelete();
            $table->string('ip_hash', 64)->nullable();
            $table->text('referrer')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestampTz('clicked_en')->useCurrent();
        });

        if ($driver === 'pgsql') {
            DB::unprepared("
                CREATE INDEX idx_short_links_codigo ON {$shortLinksTable} (codigo) WHERE activo = TRUE;
                CREATE UNIQUE INDEX uq_short_links_entidad_activa
                    ON {$shortLinksTable} (entidad_tipo, entidad_id)
                    WHERE activo = TRUE
                      AND entidad_tipo IS NOT NULL
                      AND entidad_id IS NOT NULL;
                CREATE INDEX idx_short_link_clicks_link
                    ON {$clicksTable} (short_link_id, clicked_en DESC);
            ");
        } elseif ($driver === 'sqlite') {
            Schema::table($shortLinksTable, function (Blueprint $table): void {
                $table->index('codigo');
            });

            Schema::table($clicksTable, function (Blueprint $table): void {
                $table->index(['short_link_id', 'clicked_en']);
            });

            DB::unprepared("
                CREATE UNIQUE INDEX uq_short_links_entidad_activa 
                ON {$shortLinksTable} (entidad_tipo, entidad_id) 
                WHERE activo = 1 AND entidad_tipo IS NOT NULL AND entidad_id IS NOT NULL;
            ");
        } elseif ($driver === 'mysql') {
            Schema::table($shortLinksTable, function (Blueprint $table): void {
                $table->index('codigo');
                $table->unique(['entidad_tipo', 'entidad_activa_id'], 'uq_short_links_entidad_activa');
            });

            Schema::table($clicksTable, function (Blueprint $table): void {
                $table->index(['short_link_id', 'clicked_en']);
            });
        } else {
            Schema::table($shortLinksTable, function (Blueprint $table): void {
                $table->index('codigo');
                $table->index(['entidad_tipo', 'entidad_id']);
            });

            Schema::table($clicksTable, function (Blueprint $table): void {
                $table->index(['short_link_id', 'clicked_en']);
            });
        }
    }

    public function down(): void
    {
        $shortLinksTable = config('short-links.tables.short_links', 'short_links');
        $clicksTable = config('short-links.tables.short_link_clicks', 'short_link_clicks');

        $driver = Schema::getConnection()->getDriverName();

        if (Schema::hasTable($shortLinksTable)) {
            if ($driver === 'pgsql') {
                DB::unprepared('
                    DROP INDEX IF EXISTS idx_short_link_clicks_link;
                    DROP INDEX IF EXISTS uq_short_links_entidad_activa;
                    DROP INDEX IF EXISTS idx_short_links_codigo;
                ');
            } elseif ($driver === 'sqlite') {
                DB::unprepared('
                    DROP INDEX IF EXISTS uq_short_links_entidad_activa;
                ');
            } elseif ($driver === 'mysql') {
                Schema::table($shortLinksTable, function (Blueprint $table): void {
                    $table->dropUnique('uq_short_links_entidad_activa');
                    $table->dropColumn('entidad_activa_id');
                });
            }
        }

        Schema::dropIfExists($clicksTable);
        Schema::dropIfExists($shortLinksTable);
    }
};
