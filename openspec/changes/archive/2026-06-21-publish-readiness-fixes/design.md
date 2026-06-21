# Design: Publish Readiness Fixes

## Technical Approach

The goal is to prepare the `short-links-qr` package for production-ready distribution and cross-database compatibility by solving issues in database schema definitions, migrations, factory autoloading, and dependency registration:
1. **PSR-4 Autoloading**: Move package factories from `autoload-dev` to `autoload` in `composer.json`.
2. **Database-Agnostic Column Widening**: Replace PostgreSQL raw alter commands in the migration `2026_06_22_000001_alter_short_links_codigo_length.php` with Laravel's native, driver-agnostic `$table->string('codigo', 64)->change()` builder method.
3. **Database-Agnostic Active Link Uniqueness**: Implement a robust fallback strategy to enforce database-level unique constraints preventing more than one active link per entity (`entidad_tipo`, `entidad_id`):
   - **PostgreSQL & SQLite**: Native partial unique indexes via `CREATE UNIQUE INDEX ... WHERE activo = TRUE / 1`.
   - **MySQL**: A nullable virtual generated column (`entidad_activa_id` as `CASE WHEN activo = 1 THEN entidad_id ELSE NULL END`) indexed with `entidad_tipo` under a standard unique constraint.
4. **Dynamic Service Binding**: Introduce a configurable `qr_generator` class mapping in `config/short-links.php`, bound dynamically in `ShortLinksServiceProvider`.

---

## Architecture Decisions

### Decision: Database-Agnostic Column Modifications

**Choice**: Use Laravel's standard Schema builder `Blueprint::change()` method for column modification.
**Alternatives considered**: Raw database alter statements (which target a specific driver like PgSQL).
**Rationale**: In modern Laravel versions (11.x+), the native schema builder supports column modification without external dependencies across all supported drivers (MySQL, SQLite, PgSQL). This eliminates database-specific syntax and improves codebase maintenance.

### Decision: Multi-Engine Conditional Uniqueness constraint for Active Entity

**Choice**: Use driver detection in `2026_06_21_000001_create_short_links_tables.php` to define the uniqueness constraint differently per database engine:
- For `pgsql` and `sqlite`, run a partial unique index via `DB::unprepared`:
  ```sql
  CREATE UNIQUE INDEX uq_short_links_entidad_activa ON short_links (entidad_tipo, entidad_id) WHERE activo = 1 AND entidad_tipo IS NOT NULL AND entidad_id IS NOT NULL;
  ```
- For `mysql`, define a nullable virtual generated column `entidad_activa_id` and place a unique index on `['entidad_tipo', 'entidad_activa_id']`.

**Alternatives considered**:
1. *Composite unique constraint `['entidad_tipo', 'entidad_id', 'activo']`*: Rejected. If an entity has multiple inactive links (`activo = 0`), this index would fail on the second inactive link creation.
2. *Composite key where `activo` is NULL when inactive*: Rejected. Changing the `activo` column to nullable breaks compatibility and changes the boolean API of the package.
3. *Application-level checks (e.g., in `ShortLinkService` before creation)*: Rejected. Does not prevent race conditions or guarantee database-level consistency under concurrent requests.

**Rationale**: Since MySQL does not support conditional index filters (`WHERE` clause in index creation), virtual columns serve as a standard, backward-compatible solution. SQLite and PostgreSQL natively support partial unique indexes, which allows keeping their schema clean.

### Decision: Configuration-Driven QR Generator Implementation

**Choice**: Map the concrete QR generator class under `short-links.qr_generator` config and resolve dynamically via `ShortLinksServiceProvider` container registration.
**Alternatives considered**: Specifying driver aliases or using a factory resolver mapping.
**Rationale**: Direct class-string mapping is the simplest, most transparent mechanism for Laravel developers. Resolving the class string using `$app->make()` allows custom implementation injections seamlessly.

---

## Data Flow

### Container Resolution Flow
```
Consuming App ──→ ShortLinksServiceProvider ──→ Reads config('short-links.qr_generator') ──→ Resolves Concrete Class via Container
```

### Uniqueness Verification Flow (Write Operation)
```
Database Insert / Update
    │
    ├──► Driver is PgSQL/SQLite ──► Evaluates Partial Index (WHERE activo = true/1) ──► Exception on duplicate active
    │
    └──► Driver is MySQL ────────► Calculates Virtual Column 'entidad_activa_id' ────► Evaluates Unique Constraint ──► Exception on duplicate active
```

---

## File Changes

| File | Action | Description |
|------|--------|-------------|
| [composer.json](file:///media/richard/DATOS/programas/php/Laravel/short-links-qr/composer.json) | Modify | Move factories mapping `"RichardRoman\\ShortLinks\\Database\\Factories\\": "database/factories/"` from `autoload-dev` to `autoload`. |
| [config/short-links.php](file:///media/richard/DATOS/programas/php/Laravel/short-links-qr/config/short-links.php) | Modify | Add the `'qr_generator' => \RichardRoman\ShortLinks\Laravel\Qr\EndroidQrGenerator::class` configuration line. |
| [src/Laravel/ShortLinksServiceProvider.php](file:///media/richard/DATOS/programas/php/Laravel/short-links-qr/src/Laravel/ShortLinksServiceProvider.php) | Modify | Update `QrGeneratorInterface` singleton binding to resolve class name dynamically from config. |
| [database/migrations/2026_06_21_000001_create_short_links_tables.php](file:///media/richard/DATOS/programas/php/Laravel/short-links-qr/database/migrations/2026_06_21_000001_create_short_links_tables.php) | Modify | Refactor indexes setup to support partial index (PgSQL & SQLite) and virtual generated column index (MySQL). |
| [database/migrations/2026_06_22_000001_alter_short_links_codigo_length.php](file:///media/richard/DATOS/programas/php/Laravel/short-links-qr/database/migrations/2026_06_22_000001_alter_short_links_codigo_length.php) | Modify | Replace Postgres-only raw query with driver-agnostic Schema change syntax. |

---

## Interfaces / Contracts

No new interfaces are created. The existing `QrGeneratorInterface` contract remains unmodified:
```php
namespace RichardRoman\ShortLinks\Contracts;

interface QrGeneratorInterface
{
    public function generate(string $content, array $options = []): string;
}
```

The dynamic configuration expects class strings implementing `QrGeneratorInterface`:
```php
// config/short-links.php
'qr_generator' => \RichardRoman\ShortLinks\Laravel\Qr\EndroidQrGenerator::class,
```

---

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Unit | PSR-4 Factory Autoloading | Confirm that `ShortLinkFactory` class can be resolved and instantiated dynamically. |
| Integration | Active Entity Link Uniqueness Constraint | Attempt to insert multiple active short links for the same entity and assert that a database integrity exception is thrown. Also verify that multiple *inactive* links can exist for the same entity without issues. |
| Integration | Column Widening Migration | Verify that migrating and rolling back the database succeeds on the SQLite test setup. |
| Integration | Custom QR Generator Binding | Mock or create a custom test class implementing `QrGeneratorInterface`, update config dynamically, and assert that the container resolves the custom implementation class correctly. |

---

## Migration / Rollout

No manual migration script execution is required. Standard database migrations will apply the schema changes automatically during deployment. Backward compatibility is maintained as old installations on PostgreSQL will continue to operate with the same index and column length configurations.

---

## Open Questions

- None.
