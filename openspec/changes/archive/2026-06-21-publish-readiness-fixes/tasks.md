# Tasks: Publish Readiness Fixes

## Phase 1: Infrastructure and Config

- [x] 1.1 Move the factory namespace mapping `"RichardRoman\\ShortLinks\\Database\\Factories\\": "database/factories/"` from `autoload-dev` to `autoload` in [composer.json](file:///media/richard/DATOS/programas/php/Laravel/short-links-qr/composer.json) to allow consuming apps to resolve the factory in production.
- [x] 1.2 Add the default `'qr_generator' => \RichardRoman\ShortLinks\Laravel\Qr\EndroidQrGenerator::class,` mapping inside the config array returned by [config/short-links.php](file:///media/richard/DATOS/programas/php/Laravel/short-links-qr/config/short-links.php) to support configuration-driven bindings.

## Phase 2: Database Migrations

- [x] 2.1 Refactor migration [database/migrations/2026_06_22_000001_alter_short_links_codigo_length.php](file:///media/richard/DATOS/programas/php/Laravel/short-links-qr/database/migrations/2026_06_22_000001_alter_short_links_codigo_length.php) to be database agnostic. Replace raw PostgreSQL statements in `up()` and `down()` with Laravel's native `$table->string('codigo', 64)->change()` and `$table->string('codigo', 10)->change()` schema builder methods, and remove driver checking logic.
- [x] 2.2 Refactor migration [database/migrations/2026_06_21_000001_create_short_links_tables.php](file:///media/richard/DATOS/programas/php/Laravel/short-links-qr/database/migrations/2026_06_21_000001_create_short_links_tables.php) for driver-specific active uniqueness constraint handling:
  - If driver is `sqlite`, run SQLite-specific partial unique index via `DB::unprepared`:
    ```sql
    CREATE UNIQUE INDEX uq_short_links_entidad_activa 
    ON short_links (entidad_tipo, entidad_id) 
    WHERE activo = 1 AND entidad_tipo IS NOT NULL AND entidad_id IS NOT NULL;
    ```
  - If driver is `mysql`, define a nullable virtual generated column `entidad_activa_id` as `CASE WHEN activo = 1 THEN entidad_id ELSE NULL END`, and add a unique index on `['entidad_tipo', 'entidad_activa_id']` named `uq_short_links_entidad_activa`.
  - Maintain the existing `pgsql` behavior (partial unique index where `activo = TRUE`).
  - In `down()`, handle dropping the virtual column/index for MySQL and partial unique indexes for PgSQL and SQLite.

## Phase 3: Service Provider Wiring

- [x] 3.1 Update [src/Laravel/ShortLinksServiceProvider.php](file:///media/richard/DATOS/programas/php/Laravel/short-links-qr/src/Laravel/ShortLinksServiceProvider.php): Modify the `QrGeneratorInterface::class` singleton binding to resolve class name dynamically from `config('short-links.qr_generator')` using `$this->app->make($generatorClass)`.

## Phase 4: Testing & Verification

- [x] 4.1 Create test file `tests/Feature/FactoryAutoloadTest.php` to verify factory class resolution. Assert that `ShortLinkFactory` can be resolved and instantiated via `ShortLink::factory()`.
- [x] 4.2 Create test file `tests/Feature/ShortLinkUniquenessTest.php` to verify active entity uniqueness constraint under SQLite:
  - GIVEN an active short link for an entity, WHEN creating another active short link for the same entity, THEN assert that a query exception (database integrity violation) is thrown.
  - GIVEN an inactive short link for an entity, WHEN creating another inactive short link for the same entity, THEN assert that it is successfully created without exception.
- [x] 4.3 Verify custom QR generator registration in `tests/Feature/QrGeneratorBindingTest.php`. Mock/stub a custom class implementing `QrGeneratorInterface`, register it dynamically using config, and assert that the container resolves the custom implementation class correctly.
- [x] 4.4 Run all test suites via `composer test` to confirm everything passes.

## Phase 5: Documentation & Cleanup

- [x] 5.1 Document the new `'qr_generator'` key in package configuration documentation (e.g. inside `README.md` or comments within the config file).
- [x] 5.2 Audit modified files for extraneous debugger statements, imports, or dead comments, and ensure code formatting complies with project PSR-12 coding standard.
