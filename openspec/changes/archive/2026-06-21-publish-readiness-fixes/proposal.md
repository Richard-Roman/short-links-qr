# Proposal: Publish Readiness Fixes

## Intent

Prepare the package `short-links-qr` for safe production release and wider compatibility by resolving architectural issues and database engine assumptions. Currently:
1. Database factories are under `autoload-dev`, meaning they are unavailable to consuming projects in production environments.
2. The column widening migration (`2026_06_22_000001_alter_short_links_codigo_length.php`) only runs on PostgreSQL, bypassing SQLite and MySQL.
3. The unique constraint checking active entity short links (`uq_short_links_entidad_activa`) is PostgreSQL-only, leaving MySQL and SQLite installations with potential data integrity risks.
4. The QR generator implementation (`QrGeneratorInterface`) is hardcoded to `EndroidQrGenerator`, which restricts custom generators.

## Scope

### In Scope
- **Factory Autoloading**: Move the database factories PSR-4 mapping to `autoload` in `composer.json`.
- **Database-Agnostic Column Alteration**: Make the column widening migration database-agnostic using Laravel's native schema builder methods.
- **Uniqueness Constraint Compatibility**: Implement index and constraint fallback solutions for SQLite (partial indexes) and MySQL (virtual generated columns) to ensure database-level integrity for active short links per entity.
- **Dynamic QR Generator Binding**: Allow configuring the `QrGeneratorInterface` class implementation via `config/short-links.php`.

### Out of Scope
- Re-architecting the database schema or changing table names.
- Implementing new QR generator adapters (e.g., custom SVG/HTML generators).

## Approach

### 1. Autoloading Factories
We will shift `"RichardRoman\\ShortLinks\\Database\\Factories\\": "database/factories/"` from `autoload-dev` to `autoload` in `composer.json`. This ensures consuming Laravel apps can use `ShortLink::factory()` in their seeders and test suites.

### 2. Database-Agnostic Column Widening
We will replace the PostgreSQL raw ALTER TABLE command in `2026_06_22_000001_alter_short_links_codigo_length.php` with:
```php
Schema::table($shortLinksTable, function (Blueprint $table): void {
    $table->string('codigo', 64)->change();
});
```
This requires no custom DB engine checking, as Laravel handles type changing natively for supported databases (PostgreSQL, SQLite, MySQL).

### 3. Unified Active Entity Uniqueness Index
To enforce that an entity (`entidad_tipo`, `entidad_id`) can only have **one** active short link:
- **For pgsql and sqlite**: We will run partial unique index creation using `DB::unprepared` since SQLite natively supports the `WHERE` clause for conditional indices:
  ```sql
  CREATE UNIQUE INDEX uq_short_links_entidad_activa 
  ON short_links (entidad_tipo, entidad_id) 
  WHERE activo = TRUE AND entidad_tipo IS NOT NULL AND entidad_id IS NOT NULL;
  ```
- **For mysql**: MySQL doesn't support partial index WHERE clauses. We will add a virtual generated column `entidad_activa_id` that is set to `entidad_id` when `activo = 1` and `NULL` otherwise. We will then define a unique index on `['entidad_tipo', 'entidad_activa_id']`. Because MySQL unique constraints treat `NULL` values as distinct, this correctly allows multiple inactive links while ensuring only one active link exists per entity.
  ```php
  Schema::table($shortLinksTable, function (Blueprint $table) {
      $table->string('entidad_activa_id', 36)
          ->virtualAs('CASE WHEN activo = 1 THEN entidad_id ELSE NULL END');
      $table->unique(['entidad_tipo', 'entidad_activa_id'], 'uq_short_links_entidad_activa');
  });
  ```

### 4. Dynamic QR Generator Config
We will add `qr_generator` to `config/short-links.php` pointing to the default `EndroidQrGenerator::class`.
In `ShortLinksServiceProvider.php`, we will resolve the binding dynamically:
```php
$this->app->singleton(QrGeneratorInterface::class, function ($app) {
    $generatorClass = config('short-links.qr_generator', EndroidQrGenerator::class);
    return $app->make($generatorClass);
});
```

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| [composer.json](file:///media/richard/DATOS/programas/php/Laravel/short-links-qr/composer.json) | Modified | Move factory mapping to `autoload`. |
| [database/migrations/2026_06_21_000001_create_short_links_tables.php](file:///media/richard/DATOS/programas/php/Laravel/short-links-qr/database/migrations/2026_06_21_000001_create_short_links_tables.php) | Modified | Update driver logic to apply partial indices for SQLite and virtual column uniques for MySQL. |
| [database/migrations/2026_06_22_000001_alter_short_links_codigo_length.php](file:///media/richard/DATOS/programas/php/Laravel/short-links-qr/database/migrations/2026_06_22_000001_alter_short_links_codigo_length.php) | Modified | Use database-agnostic Schema change logic and remove Postgres raw check. |
| [config/short-links.php](file:///media/richard/DATOS/programas/php/Laravel/short-links-qr/config/short-links.php) | Modified | Add `qr_generator` default config mapping. |
| [src/Laravel/ShortLinksServiceProvider.php](file:///media/richard/DATOS/programas/php/Laravel/short-links-qr/src/Laravel/ShortLinksServiceProvider.php) | Modified | Update singleton binding to read from config dynamically. |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| PostgreSQL/SQLite migration syntax issues with `DB::unprepared` | Low | Test migration runs across SQLite (our test DB) and run validation suites. |
| MySQL generated column incompatibility | Low | Generated columns are supported in MySQL 5.7+ and MariaDB 10.2+, which is standard for modern Laravel environments. |
| Incorrect custom QR Generator binding configurations | Low | Default value points back to EndroidQrGenerator ensuring backward compatibility. |

## Rollback Plan

If issues arise:
1. Revert changes to migration files and re-run migrations from scratch.
2. If in production, rollback migration `2026_06_22_000001_alter_short_links_codigo_length` and `2026_06_21_000001_create_short_links_tables` to their previous state.
3. Reset configuration and `composer.json` changes via Git revert.

## Dependencies

- None.

## Success Criteria

- [ ] PSR-4 autoloading of factories is configured under standard `autoload` block.
- [ ] Database migrations complete successfully on SQLite test environment.
- [ ] Active entity short link uniqueness constraint is enforced properly in SQLite testing.
- [ ] `QrGeneratorInterface` is resolved dynamically based on the configured class in config.
