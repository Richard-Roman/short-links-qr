# Database Specification

## Purpose

Enforce data consistency, migration compatibility, and schema integrity across supported database engines (PostgreSQL, SQLite, and MySQL) in the `short-links-qr` package.

## Requirements

### Requirement: Database-Agnostic Column Widening Migration

The database migration responsible for widening the `codigo` column on the short links table MUST perform the schema modifications using Laravel's native schema builder methods, avoiding driver-specific raw queries or statements for the column modification. This ensures compatibility across all supported database drivers (PostgreSQL, MySQL, SQLite) during both execution and rollback.

#### Scenario: Database-agnostic column widening migration up execution
- GIVEN a database migration execution using any supported driver (MySQL, PgSQL, SQLite)
- WHEN the `alter_short_links_codigo_length` migration is executed via the `up` method
- THEN the `codigo` column on the `short_links` table is altered to `varchar(64)` using Laravel native Schema builder `change()` method.

#### Scenario: Database-agnostic column narrowing migration rollback
- GIVEN a database migration execution using any supported driver (MySQL, PgSQL, SQLite)
- WHEN the `alter_short_links_codigo_length` migration is rolled back via the `down` method
- THEN the `codigo` column on the `short_links` table is altered back to its original length using Laravel native Schema builder `change()` method.

### Requirement: Unique Short Link Constraint per Active Entity

To prevent data conflicts and redirect ambiguity, an entity (`entidad_tipo`, `entidad_id`) MUST NOT have more than one active (`activo = true` or `activo = 1`) short link in the database at any given time. However, multiple inactive short links (`activo = false` or `activo = 0`) MAY exist for the same entity.

#### Scenario: Enforcing active uniqueness on PgSQL and SQLite
- GIVEN a database connection configured for PostgreSQL or SQLite
- AND migrations are successfully executed
- AND an active short link exists for entity type `User` and entity ID `123e4567-e89b-12d3-a456-426614174000` (`activo` set to true/1)
- WHEN a client attempts to insert or update another short link for entity type `User` and entity ID `123e4567-e89b-12d3-a456-426614174000` with `activo` set to true/1
- THEN a database integrity exception is thrown.

#### Scenario: Allowing multiple inactive short links on PgSQL and SQLite
- GIVEN a database connection configured for PostgreSQL or SQLite
- AND migrations are successfully executed
- AND an inactive short link exists for entity type `User` and entity ID `123e4567-e89b-12d3-a456-426614174000` (`activo` set to false/0)
- WHEN a client inserts or updates another short link for entity type `User` and entity ID `123e4567-e89b-12d3-a456-426614174000` with `activo` set to false/0
- THEN the short link is successfully stored in the database.

#### Scenario: Enforcing active uniqueness on MySQL
- GIVEN a database connection configured for MySQL
- AND migrations are successfully executed
- AND an active short link exists for entity type `User` and entity ID `123e4567-e89b-12d3-a456-426614174000` (`activo` set to 1)
- WHEN a client attempts to insert or update another short link for entity type `User` and entity ID `123e4567-e89b-12d3-a456-426614174000` with `activo` set to 1
- THEN a database integrity exception is thrown.

#### Scenario: Allowing multiple inactive short links on MySQL
- GIVEN a database connection configured for MySQL
- AND migrations are successfully executed
- AND an inactive short link exists for entity type `User` and entity ID `123e4567-e89b-12d3-a456-426614174000` (`activo` set to 0)
- WHEN a client inserts or updates another short link for entity type `User` and entity ID `123e4567-e89b-12d3-a456-426614174000` with `activo` set to 0
- THEN the short link is successfully stored in the database.
