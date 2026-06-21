# Tasks: QR Package Final Fixes

## Phase 1: Contracts & Interfaces

- [x] 1.1 In `src/Contracts/ShortLinkRepositoryInterface.php`, add method declaration `public function deactivateByCodigo(string $codigo): void;` — ensures LSP compliance with the existing concrete implementation and enables alternative implementations to conform.

## Phase 2: Composer Dependency

- [x] 2.1 In `composer.json`, move `"endroid/qr-code": "^6.0"` from the `require-dev` block to the `require` block — the default driver is `EndroidQrGenerator` and production installs must have this package available.
- [x] 2.2 In `composer.json`, remove the `"endroid/qr-code"` entry from the `suggest` block — it is now a hard dependency and the suggest hint is misleading/redundant.

## Phase 3: Model Timestamps & Casts

- [x] 3.1 In `src/Laravel/Models/ShortLink.php`:
  - Replace `public $timestamps = false` with `public $timestamps = true`
  - Add `const CREATED_AT = 'creado_en';` (map Eloquent's created-at slot to the actual column)
  - Add `const UPDATED_AT = null;` (disable the update timestamp — no `updated_at` column exists)
- [x] 3.2 In `src/Laravel/Models/ShortLink.php`, add `'creado_en' => 'datetime'` to the `casts()` method (or `$casts` array) so `ShortLink::create([...])` returns a Carbon instance for `creado_en` without a `fresh()` reload.
- [x] 3.3 In `src/Laravel/Models/ShortLinkClick.php`:
  - Replace `public $timestamps = false` with `public $timestamps = true`
  - Add `const CREATED_AT = 'clicked_en';`
  - Add `const UPDATED_AT = null;`
- [x] 3.4 In `src/Laravel/Models/ShortLinkClick.php`, add `'clicked_en' => 'datetime'` to the `casts()` method (or `$casts` array).

## Phase 4: Config Charset Alignment

- [x] 4.1 In `config/short-links.php`, remove the character `1` from the `generator.charset` string — the `route_pattern` regex uses `[2-9a-zA-Z]`, making `1` an unreachable character that would silently produce codes that fail route matching.

## Phase 5: Exception Messages to English

- [x] 5.1 In `src/Core/Exceptions/QrGeneratorNotAvailableException.php`, translate the exception message string to English (e.g., `'QR generator is not available. Install endroid/qr-code.'`).
- [x] 5.2 In `src/Core/Exceptions/InvalidCodeFormatException.php`, translate the exception message string to English (e.g., `"The code '{$codigo}' does not match the required format."` ).
- [x] 5.3 In `src/Core/Exceptions/DuplicateCodeException.php`, translate the exception message string to English (e.g., `"The code '{$codigo}' already exists."`).

## Phase 6: RedirectService Refactor (Eliminate Double Query)

- [x] 6.1 In `src/Core/Services/RedirectService.php`, change the return type of `resolve(string $codigo)` from `?string` to `?ShortLink` — return the full domain object retrieved from the repository instead of extracting only `urlDestino`.
- [x] 6.2 In `src/Laravel/Http/Controllers/RedirectController.php`:
  - Replace the call `$url = $this->redirectService->resolve($codigo)` with `$shortLink = $this->redirectService->resolve($codigo)`
  - Extract the destination URL via `$shortLink->urlDestino` (or the appropriate accessor)
  - Pass `$shortLink` directly to `RecordClickAction` (or equivalent) — remove the second `findActiveByCodigo()` query call entirely
- [x] 6.3 If a `RedirectServiceInterface` (or equivalent) exists, update its `resolve()` signature to match the new `?ShortLink` return type — grep with `grep -r "RedirectServiceInterface" src/` to confirm file path before editing.

## Phase 7: README Cleanup

- [x] 7.1 In `README.md`, locate and delete the entire "Registro en Packagist" section (heading + all body content under it) — this is internal scaffolding documentation irrelevant to package consumers.

## Phase 8: Verification

- [x] 8.1 Run `./vendor/bin/phpunit --no-coverage` and confirm all tests pass with zero failures or errors — this is the regression gate for all phases above.
- [x] 8.2 Run `composer validate --strict` to confirm `composer.json` is syntactically valid and passes Composer's strict schema checks after the dependency block changes.
