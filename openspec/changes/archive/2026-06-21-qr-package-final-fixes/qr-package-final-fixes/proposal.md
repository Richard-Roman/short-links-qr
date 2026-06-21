# Proposal: QR Package Final Fixes

## Intent

A pre-publication code review of the `short-links-qr` Laravel package identified **6 hard blockers and 2 warnings** that would cause runtime bugs, broken contracts, or inappropriate content for public consumers if shipped as-is.

Specifically:
- The repository interface is incomplete (missing a method its implementation declares), which breaks LSP and any future alternative implementations.
- The Eloquent timestamp configuration is contradictory: `$timestamps = false` paired with a custom `CREATED_AT` constant silently produces `null` timestamps after model creation.
- The default QR generator (`EndroidQrGenerator`) is backed by a `require-dev` dependency, meaning production installs would fail at runtime.
- The charset in config contains character `1` which is excluded by the `route_pattern` regex, so codes with `1` would silently fail validation.
- The README contains internal Packagist registration instructions that are irrelevant and confusing to consumers.
- Exception messages are in Spanish, inappropriate for a public open-source package.
- The redirect flow performs two identical queries per request (one in `RedirectService`, one in `RedirectController`).

All issues are self-contained, mechanical fixes with no architectural redesign required.

## Scope

### In Scope

- **Blocker 1**: Add `deactivateByCodigo(string $codigo): void` to `ShortLinkRepositoryInterface`
- **Blocker 2**: Move `endroid/qr-code: ^6.0` from `require-dev` to `require` in `composer.json`; remove it from `suggest`
- **Blocker 3**: Fix timestamp configuration in `ShortLink` model (`$timestamps = true`, `const UPDATED_AT = null`, `const CREATED_AT = 'creado_en'`); same fix in `ShortLinkClick` model for `clicked_en`
- **Blocker 4**: Add `'creado_en' => 'datetime'` cast to `ShortLink` model and `'clicked_en' => 'datetime'` cast to `ShortLinkClick` model
- **Blocker 5**: Remove character `1` from `generator.charset` in `config/short-links.php`
- **Blocker 6**: Remove the "Registro en Packagist" section from `README.md`
- **Warning 7**: Refactor `RedirectService::resolve()` to return `?ShortLink` instead of `?string`, eliminating the double query per redirect in `RedirectController`
- **Warning 8**: Translate all exception messages to English in `QrGeneratorNotAvailableException`, `InvalidCodeFormatException`, and `DuplicateCodeException`

### Out of Scope

- Adding MySQL/PostgreSQL test matrices to CI (requires infrastructure changes)
- Creating `SECURITY.md`
- Translating `README.md` body to English (deferred by user decision)
- Moving `InMemoryRedirectCache` to test support namespace
- Removing `SmokeTest`
- Adding PNG validity test for QR generators

## Approach

All changes are surgical and isolated. No new abstractions, no architecture changes.

**Execution order** (to respect dependency chains):
1. Fix the interface first (`ShortLinkRepositoryInterface`) so the rest of the codebase stays type-safe throughout the change.
2. Fix models (`ShortLink`, `ShortLinkClick`) — timestamp + casts — independently since they have no cross-dependencies.
3. Fix config (`short-links.php`) — remove `1` from charset.
4. Fix `composer.json` — move `endroid/qr-code` to `require`.
5. Fix exceptions — translate messages to English.
6. Refactor `RedirectService` + `RedirectController` for the double-query fix (these two files must be changed together atomically).
7. Fix `README.md` — remove internal section.

Each fix is independently verifiable. The test suite (`composer test`) is the regression gate after all changes.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `src/Contracts/ShortLinkRepositoryInterface.php` | Modified | Add missing `deactivateByCodigo()` declaration |
| `composer.json` | Modified | Move `endroid/qr-code` to `require`; remove from `suggest` |
| `src/Laravel/Models/ShortLink.php` | Modified | Fix timestamps config; add `datetime` cast for `creado_en` |
| `src/Laravel/Models/ShortLinkClick.php` | Modified | Fix timestamps config; add `datetime` cast for `clicked_en` |
| `config/short-links.php` | Modified | Remove `1` from `generator.charset` |
| `README.md` | Modified | Remove "Registro en Packagist" section |
| `src/Core/Services/RedirectService.php` | Modified | Change return type to `?ShortLink` |
| `src/Laravel/Http/Controllers/RedirectController.php` | Modified | Use `ShortLink` returned by service; drop second query |
| `src/Core/Exceptions/QrGeneratorNotAvailableException.php` | Modified | Translate message to English |
| `src/Core/Exceptions/InvalidCodeFormatException.php` | Modified | Translate message to English |
| `src/Core/Exceptions/DuplicateCodeException.php` | Modified | Translate message to English |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| `$timestamps = true` causes Eloquent to try updating `updated_at` on non-existent column | Med | `const UPDATED_AT = null` disables the update timestamp; verify with existing migration schema |
| `RedirectService` return-type change breaks callers beyond `RedirectController` | Low | Grep for all callers of `resolve()` before making the change; confirm only one caller exists |
| Moving `endroid/qr-code` to `require` breaks installs where it was excluded | Low | It was already the default driver; any working install already had it; `^6.0` constraint is stable |
| Removing `1` from charset invalidates existing short codes in user DBs | Low | Codes are generated, not user-provided; no existing code will contain `1` unless manually inserted — document in CHANGELOG |

## Rollback Plan

All changes are in tracked files. Rollback is a `git revert` or `git checkout` per-file.

1. If the timestamp fix causes issues: revert `ShortLink.php` and `ShortLinkClick.php` to `$timestamps = false` and investigate migration columns.
2. If the `RedirectService` refactor breaks routing: revert both `RedirectService.php` and `RedirectController.php` together (they form an atomic pair).
3. If moving `endroid/qr-code` causes dependency conflicts: revert `composer.json` and run `composer update`.

No database migrations are involved; no data is at risk.

## Dependencies

- No new external dependencies introduced.
- `endroid/qr-code: ^6.0` is already present in the lock file (was `require-dev`); moving it to `require` only changes its installation guarantee.

## Success Criteria

- [ ] `ShortLinkRepositoryInterface` declares `deactivateByCodigo(string $codigo): void`
- [ ] `composer.json` has `endroid/qr-code` under `require`, not `require-dev`; absent from `suggest`
- [ ] `ShortLink::create([...])` returns a model with a non-null `creado_en` Carbon instance without needing a `fresh()` reload
- [ ] `ShortLinkClick::create([...])` returns a model with a non-null `clicked_en` Carbon instance
- [ ] `config/short-links.php` `generator.charset` does not contain `1`
- [ ] `README.md` does not contain the "Registro en Packagist" section
- [ ] Each redirect request issues exactly one `SELECT` on `short_links` (verifiable via query log in tests)
- [ ] All exception messages are in English
- [ ] `composer test` passes with no regressions
