# Changelog

All notable changes to `richard-roman/short-links-qr` are documented in this file.

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
