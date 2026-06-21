# Tasks: Corrección gaps post-revisión (v1.1.1)

**Change:** `fix-publish-readiness-gaps`  
**Proposal:** `openspec/changes/fix-publish-readiness-gaps/proposal.md`  
**Base:** work staged de `publish-readiness-fixes` (sin revertir) sobre tag **`v1.1.0`**  
**Release objetivo:** `richard-roman/short-links-qr` tag **`v1.1.1`**  
**Repo paquete:** `short-links-qr`  
**Host:** `web-iot-fisi` (sibling)

**Convención:** `[P]` = repo paquete · `[H]` = host `web-iot-fisi`

---

## Precondición

> El código de `publish-readiness-fixes` (factories autoload, `qr_generator`, migraciones multi-DB, tests nuevos, README) MUST estar presente en el working tree/stage **antes** de aplicar este change. Este change **no reimplementa** publish-readiness; solo corrige gaps y publica.

---

## Fase 1: Fix tests (bloqueador)

> **Objetivo:** `composer test` ejecuta sin fatal error.  
> **Criterio de salida:** `QrGeneratorBindingTest` verde; stub alineado a contrato real.

- [x] **1.1 [P]** Modificar `tests/Feature/QrGeneratorBindingTest.php` — clase `CustomTestQrGenerator`: reemplazar `generate(...)` por `generatePng(string $shortUrl): string` retornando `'custom-qr-content'`.
- [x] **1.2 [P]** Modificar `tests/Feature/QrGeneratorBindingTest.php` — en `test_resolves_custom_qr_generator_from_config`:
  - Tras `config(['short-links.qr_generator' => ...])`, llamar `$this->app->forgetInstance(QrGeneratorInterface::class)`.
  - Aserción: `$generator->generatePng('https://example.com/l/abc')` === `'custom-qr-content'`.
- [x] **1.3 [P]** Ejecutar `vendor/bin/phpunit tests/Feature/QrGeneratorBindingTest.php` — verde (2 tests).

---

## Fase 2: Idempotencia migración alter

> **Objetivo:** `2026_06_22_*` no falla en PG dev que ya amplió `codigo` a 64 (v1.1.0).  
> **Criterio de salida:** guard pre-`change()` + Schema builder agnóstico conservado.

- [x] **2.1 [P]** Modificar `database/migrations/2026_06_22_000001_alter_short_links_codigo_length.php`:
  - Early return si tabla no existe (mantener).
  - Restaurar método privado `codigoNeedsWiden(string $table): bool`:
    - **pgsql:** `information_schema.columns` → `max_len < 64`.
    - **sqlite:** `PRAGMA table_info` o equivalente → longitud declarada &lt; 64.
    - **mysql:** `information_schema` o `SHOW COLUMNS` → `CHARACTER_MAXIMUM_LENGTH < 64`.
  - En `up()`: solo ejecutar `$table->string('codigo', 64)->change()` si `codigoNeedsWiden()` es true.
  - En `down()`: solo estrechar a 10 si columna es 64 (guard simétrico opcional; documentar riesgo truncado en CHANGELOG).
- [x] **2.2 [P]** Verificar que migración create (`2026_06_21_*`) sigue creando `codigo string(64)` en installs nuevas — sin cambios adicionales salvo bug encontrado.

### Verificación Fase 2

- [x] **2.V1 [P]** `composer test` con `RefreshDatabase` (SQLite Testbench) — suite completa pasa tras guard alter.

---

## Fase 3: Higiene de commit

> **Objetivo:** Release limpio sin artefactos de IDE/SDD.

- [x] **3.1 [P]** Unstage/excluir `.atl/skill-registry.md` del commit del paquete (`git restore --staged .atl/skill-registry.md` o equivalente).
- [x] **3.2 [P]** Opcional: añadir `.atl/` a `.gitignore` del repo paquete si no debe versionarse.
- [x] **3.3 [P]** Revisar stage final: incluye publish-readiness (composer, config, migrations, provider, tests, README, openspec archivado) **sin** `.atl/skill-registry.md`.

---

## Fase 4: Documentación y regresión paquete

> **Objetivo:** ≥43 tests PASS · CHANGELOG v1.1.1.

- [x] **4.1 [P]** Actualizar `CHANGELOG.md` — sección **`[1.1.1]`**:
  - Publish-readiness: factories prod autoload, `qr_generator` config, unicidad activa multi-DB, alter agnóstico.
  - Hardening: fix `QrGeneratorBindingTest`, idempotencia alter `codigo`.
- [x] **4.2 [P]** Ejecutar `composer validate --strict` — OK.
- [x] **4.3 [P]** Ejecutar `composer test` — **≥43 tests PASS** (40 v1.1.0 + 3 publish-readiness), exit 0.

---

## Fase 5: Release v1.1.1

> **Objetivo:** Tag publicado · CI verde.  
> **Precondición:** Fases 1–4 completas.

- [ ] **5.1 [P]** Commit en repo `short-links-qr`: mensaje tipo `Release v1.1.1: publish-readiness fixes, test and migration hardening`.
- [ ] **5.2 [P]** Tag anotado `v1.1.1` y push a `Richard-Roman/short-links-qr`.
- [ ] **5.3 [P]** Verificar CI GitHub Actions verde en tag `v1.1.1` (workflow trigger `v*`).

### Verificación Fase 5

- [ ] **5.V1 [P]** Smoke: `composer require richard-roman/short-links-qr:^1.1` en app limpia resuelve `v1.1.1`; provider descubierto; `ShortLink::factory()` autoload OK.

---

## Fase 6: Integración host (mínima)

> **Objetivo:** Host consume v1.1.1 · sin regresión.  
> **Precondición:** Fase 5 con tag publicado y CI verde.

- [ ] **6.1 [H]** Confirmar `composer.json` host mantiene `"richard-roman/short-links-qr": "^1.1"` (ya aplicado en configurable-custom-short-links).
- [ ] **6.2 [H]** `composer update richard-roman/short-links-qr` — lock resuelve `v1.1.1`.
- [ ] **6.3 [H]** `php artisan migrate` — no-op esperado si alter PG ya aplicada; sin error.
- [ ] **6.4 [H]** Ejecutar **en serie** (sin paralelismo):
  - `php artisan test tests/Feature/ShortLinks/ShortLinkEntregableTest.php`
  - `php artisan test tests/Feature/Projects/`

### Verificación Fase 6

- [ ] **6.V1 [H]** `composer show richard-roman/short-links-qr` → `v1.1.1`, ref dist coincide con tag.
- [ ] **6.V2 [H]** `vendor/richard-roman/short-links-qr` incluye `qr_generator` en config y `getTable()` — sin parches locales.

---

## Orden de implementación recomendado

```
Fase 1 (fix QrGeneratorBindingTest)
  → Fase 2 (idempotencia alter)
  → Fase 3 (higiene commit)
  → Fase 4 (CHANGELOG + composer test)
  → Fase 5 (tag v1.1.1)
  → Fase 6 (host update + regresión)
```

**No iniciar Fase 6** hasta Fase 5 con CI verde.

**Siguiente acción inmediata:** `/sdd-apply fix-publish-readiness-gaps Fase 5` — tareas **5.1 → 5.3**.

---

## Mapa tareas ↔ criterios de éxito (proposal)

| Success criterion (proposal) | Tarea principal |
|------------------------------|-----------------|
| ≥43 tests PASS | 1.3, 2.V1, 4.3 |
| QrGeneratorBindingTest `generatePng` | 1.1, 1.2 |
| Alter idempotente PG | 2.1, 2.V1 |
| Commit sin `.atl/skill-registry.md` | 3.1, 3.3 |
| CI verde v1.1.1 | 5.2, 5.3 |
| Host v1.1.0 → v1.1.1 | 6.2, 6.V1 |
| CHANGELOG v1.1.1 | 4.1 |
| Regresión entregables/projects | 6.4 |

---

## Notas para sdd-apply

1. **No revertir** publish-readiness staged; solo parchear gaps.
2. **`forgetInstance`** en test QR es obligatorio — singleton ya resuelto en boot del TestCase.
3. **Host tests:** ejecutar suites en serie para evitar carrera en `web_iot_fisi_test` (PostgreSQL).
4. **Rollback:** pin `"1.1.0"` en host si v1.1.1 falla; BD compatible hacia atrás.
