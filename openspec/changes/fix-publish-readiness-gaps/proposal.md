# Proposal: Corrección de gaps post-revisión (publish-readiness)

## Intent

El change `publish-readiness-fixes` está implementado y staged en el repo `short-links-qr`, pero la revisión manual detectó **bloqueadores y riesgos** antes de commit/tag:

1. **Suite rota** — `QrGeneratorBindingTest` implementa `generate()` en el stub, pero `QrGeneratorInterface` exige `generatePng(string $shortUrl): string`. PHPUnit falla con fatal error antes de ejecutar tests.
2. **Migración alter sin idempotencia** — se eliminó el guard `codigoNeedsWiden()` de v1.1.0; re-ejecutar en PG dev puede fallar o ser frágil.
3. **Artefactos ajenos al paquete** — `.atl/skill-registry.md` staged; no pertenece al release Composer.
4. **Verify engañoso** — `verify-report.md` archivado marca tests ✅ pero la ejecución fue omitida; no hay evidencia runtime real.
5. **Host desincronizado** — `web-iot-fisi` sigue en `v1.1.0`; los fixes no llegan hasta tag + `composer update`.

Este change corrige lo anterior **sin revertir** la intención de publish-readiness (factories prod, QR configurable, unicidad multi-DB, alter agnóstico).

## Scope

### In Scope

- Corregir `tests/Feature/QrGeneratorBindingTest.php` (stub + aserciones alineadas a `QrGeneratorInterface::generatePng`).
- Restaurar idempotencia en `2026_06_22_000001_alter_short_links_codigo_length.php`:
  - Solo ejecutar `->change()` si la columna `codigo` tiene longitud &lt; 64.
  - Mantener enfoque agnóstico (Schema builder) para PG/SQLite/MySQL.
- Excluir `.atl/skill-registry.md` del commit del paquete (unstage + `.gitignore` opcional en repo paquete).
- Ejecutar `composer test` y confirmar suite verde (40 tests v1.1.0 + 3 nuevos ≥ 43).
- Actualizar `CHANGELOG.md` con sección **`[1.1.1]`** (patch semver).
- Commit + tag anotado **`v1.1.1`** + push; CI verde.
- Integración host mínima: `web-iot-fisi` bump lock a `^1.1` resolviendo `v1.1.1`, `composer update`, regresión entregables/projects.

### Out of Scope

- Re-arquitectura del change `publish-readiness-fixes` (factories, QR config, unicidad MySQL/SQLite ya implementados).
- UI host para slugs manuales (`demo-2026`) — change futuro.
- Matrix CI MySQL/PgSQL en runtime (solo SQLite Testbench hoy); documentar como follow-up.
- Test automatizado de rollback `down()` de migración alter (sugerido, no bloqueante v1.1.1).

## Approach

### Fase 1 — Fix tests (bloqueador)

```php
// CustomTestQrGenerator — alinear con contrato real
public function generatePng(string $shortUrl): string
{
    return 'custom-qr-content';
}

// Aserción
$this->assertSame('custom-qr-content', $generator->generatePng('https://example.com/l/abc'));
```

Invalidar singleton cache si el test custom configura después del boot: `$this->app->forgetInstance(QrGeneratorInterface::class)` antes de `make()`.

### Fase 2 — Idempotencia migración alter

Combinar lo mejor de v1.1.0 y publish-readiness:

```
IF tabla no existe → return
IF driver pgsql → consultar information_schema; IF max_len >= 64 → return
IF driver sqlite/mysql → consultar pragma/column type equivalente O confiar en change() idempotente de Laravel
Schema::table(...)->string('codigo', 64)->change()
```

Prioridad: **no fallar** en host PG que ya aplicó alter v1.1.0.

### Fase 3 — Limpieza commit

- `git restore --staged .atl/skill-registry.md` (o no incluir en commit).
- Un solo commit: `Release v1.1.1: publish-readiness fixes, test and migration hardening`.
- Tag `v1.1.1`, push, verificar CI.

### Fase 4 — Host

```bash
# web-iot-fisi
composer update richard-roman/short-links-qr
php artisan migrate   # no-op esperado si alter ya aplicada
php artisan test tests/Feature/ShortLinks/ShortLinkEntregableTest.php tests/Feature/Projects/
```

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `tests/Feature/QrGeneratorBindingTest.php` | Modified | Stub `generatePng`, aserciones, forgetInstance |
| `database/migrations/2026_06_22_*_alter_*.php` | Modified | Guard idempotente pre-`change()` |
| `CHANGELOG.md` | Modified | Entrada `[1.1.1]` |
| `.atl/skill-registry.md` | Removed from commit | Excluir del repo paquete |
| `web-iot-fisi/composer.lock` | Modified | Lock → v1.1.1 |
| Staged publish-readiness (sin revertir) | Included in v1.1.1 | factories autoload, qr_generator, create multi-DB, README |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| `change()` requiere capability no disponible en algún driver | Med | Guard idempotente; probar `composer test` (SQLite); documentar PG host ya migrado |
| Re-ejecutar alter en PG con columna ya 64 | Med | Guard `codigoNeedsWiden()` antes de `change()` |
| Editar migración `create` ya publicada no afecta installs existentes | Low | Documentar en CHANGELOG: beneficio solo fresh installs / MySQL/SQLite nuevos |
| Host tests en paralelo sobre `web_iot_fisi_test` | Med | Ejecutar regresión en serie (lección Fase 6 configurable-custom-short-links) |

## Rollback Plan

1. **Paquete:** pin host `"richard-roman/short-links-qr": "1.1.0"` + `composer update`.
2. **BD:** columnas ampliadas a 64 y constraints nuevos son **compatibles hacia atrás**; no rollback destructivo requerido.
3. **Git:** revert commit v1.1.1 en repo paquete si CI falla post-push (antes de bump host).

## Dependencies

- Tag `v1.1.0` publicado y CI verde (cumplido).
- Change `publish-readiness-fixes` staged localmente (base del patch).
- Host `web-iot-fisi` en `^1.1` (cumplido desde configurable-custom-short-links Fase 6).

## Success Criteria

- [ ] `composer test` en `short-links-qr` — **≥43 tests PASS**, exit 0.
- [ ] `QrGeneratorBindingTest` usa `generatePng` y pasa con binding custom desde config.
- [ ] Migración alter es idempotente en PG (no falla si `codigo` ya es 64).
- [ ] Commit v1.1.1 **sin** `.atl/skill-registry.md`.
- [ ] CI GitHub Actions verde en tag `v1.1.1`.
- [ ] Host `composer show` resuelve `v1.1.0` → `v1.1.1`; regresión entregables + projects PASS.
- [ ] `CHANGELOG.md` documenta v1.1.1 (publish-readiness + hardening).
