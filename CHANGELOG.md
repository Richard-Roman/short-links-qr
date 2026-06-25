# Changelog

All notable changes to `richard-roman/short-links-qr` are documented in this file.

## [1.2.0] - 2026-06-25

### Added

- **Async redirect telemetry**: click registration (`INSERT` + counter `INCREMENT`) is now dispatched via `ProcessShortLinkClickJob` to a background queue, eliminating write latency from the redirect HTTP response.
- **`ShortLinkService::deactivate(string $codigo): void`**: public service-layer method to logically deactivate a short link by code. Delegates to `ShortLinkRepositoryInterface::deactivateByCodigo()`, invalidating the redirect cache automatically.
- **`ShortLinkService::rotate(string $codigoViejo, ?string $nuevoCodigo = null): ShortLink`**: atomic link rotation wrapped in a `DB::transaction()`. Deactivates the old link and creates a new one cloning all metadata (`urlDestino`, `entidadTipo`, `entidadId`, `titulo`, `creadoPorId`). Throws `ShortLinkNotFoundException` if the old code does not exist or is already inactive.
- **`ShortLinkNotFoundException`**: new domain exception (`DomainException`) thrown when attempting to rotate a non-existent or inactive link.
- **`ShortLinks` Facade PHPDoc updated**: `@method` annotations for `deactivate()` and `rotate()` added for full IDE autocompletion support.

### Changed

- `RecordClickAction` no longer performs synchronous DB writes during redirect. It dispatches `ProcessShortLinkClickJob` instead.
- `InMemoryShortLinkRepository` (test support class) now updates both `$byCodigo` and `$byEntity` indexes on `deactivateByCodigo()`, fixing a stale-entity bug that caused `create()` to incorrectly reuse a just-deactivated link.

### Compatibility

- Semver minor release: no breaking changes. Consumers on `^1.1` can update to `^1.2` without any config or code changes.
- Requires a configured queue driver (`QUEUE_CONNECTION`) for async click recording. Falls back to sync if `QUEUE_CONNECTION=sync`.

## [1.1.2] - 2026-06-21

### Fixed

- Changed `$timestamps = true` with custom constants on `ShortLink` and `ShortLinkClick` models to fix timestamp hydration on `create()`.
- Added `datetime` casts to `creado_en` and `clicked_en` to return Carbon instances.
- Refactored `RedirectService` and `RedirectController` to eliminate double SELECT queries during redirection.
- `deactivateByCodigo` method added to `ShortLinkRepositoryInterface` for LSP compliance.
- All exception messages translated to English for public package readiness.
- Removed internal packagist documentation from `README.md`.

### Changed

- `endroid/qr-code` moved from `require-dev` to `require` to ensure default QR generation works out-of-the-box in production.

## [1.1.1] - 2026-06-21

### Added

- `qr_generator` config key for container-resolved `QrGeneratorInterface` implementations.
- Database-level unique constraint for one active short link per entity on PostgreSQL, SQLite, and MySQL (virtual column on MySQL).
- Feature tests: factory production autoload, QR generator binding, active-entity uniqueness.

### Changed

- `ShortLinkFactory` PSR-4 mapping moved from `autoload-dev` to `autoload` for consuming apps in production.
- Column widening migration (`2026_06_22_*`) uses Laravel Schema `change()` across drivers with idempotent guards (`codigoNeedsWiden` / `codigoNeedsNarrow`).
- `ShortLinksServiceProvider` resolves QR generator class from `config('short-links.qr_generator')`.

### Fixed

- `QrGeneratorBindingTest` stub aligned to `QrGeneratorInterface::generatePng()`.
- Alter migration no longer fails on PostgreSQL installations that already widened `codigo` to 64 characters (v1.1.0).

### Notes

- Fresh installs on MySQL/SQLite gain active-entity uniqueness that was previously PostgreSQL-only.
- Migration `down()` narrowing `codigo` to 10 characters may truncate codes longer than 10 if executed manually.

### Compatibility

- Semver patch release: consumers on `^1.1` can update without behavior change when using default config.

## [1.1.0] - 2026-06-18

### Added

- Configurable code generator via `generator.length` and `generator.charset` (env: `SHORT_LINKS_LENGTH`, `SHORT_LINKS_CHARSET`).
- Dynamic route pattern via `route_pattern` (env: `SHORT_LINKS_ROUTE_PATTERN`), used in Laravel routes and core validation.
- Optional manual codes: `ShortLinks::create(..., codigo: 'mi-slug')` with lowercase normalization and format validation.
- `InvalidCodeFormatException` when a manual or auto-generated code does not match `route_pattern`.
- Incremental migration to extend `codigo` column to 64 characters on existing installations.

### Changed

- `RandomCodeGenerator` accepts charset and length via constructor (defaults preserve v1.0 behavior).
- `ShortLinkService` validates codes against `route_pattern`; auto-generated codes are re-validated before insert.
- Eloquent models `ShortLink` and `ShortLinkClick` resolve table names from `config('short-links.tables.*')` via `getTable()`.
- New installations create `codigo` as `string(64)`.

### Compatibility

- Semver minor release: consumers on `^1.0` can update to `^1.1` without behavior change when using default config.
- Existing 8-character codes remain valid after the column migration.

## [1.0.0] - 2026-06-17

### Added

- Initial release: short links, QR codes, redirect analytics, entity resolvers, Laravel Facade and ServiceProvider.
- Default 8-character codes with unambiguous charset (`[a-hjkmnp-z2-9]{8}`).
- Public routes `GET /l/{codigo}` and `GET /l/{codigo}/qr`.
