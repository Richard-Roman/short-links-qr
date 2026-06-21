# richard-roman/short-links-qr

Paquete Composer de short links + códigos QR para aplicaciones Laravel.

**Versión:** 1.1.0  
**Requisitos:** PHP ^8.3, Laravel ^11|^12|^13  
**Repositorio:** [github.com/Richard-Roman/short-links-qr](https://github.com/Richard-Roman/short-links-qr)  
**Packagist:** [packagist.org/packages/richard-roman/short-links-qr](https://packagist.org/packages/richard-roman/short-links-qr)

## Instalación

### Packagist (recomendado)

```bash
composer require richard-roman/short-links-qr:^1.1
composer require endroid/qr-code:^6.0
```

### VCS (sin Packagist)

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/Richard-Roman/short-links-qr"
        }
    ],
    "require": {
        "richard-roman/short-links-qr": "^1.1",
        "endroid/qr-code": "^6.0"
    }
}
```

El paquete se auto-descubre vía `ShortLinksServiceProvider`. Ejecutar migraciones:

```bash
php artisan migrate
```

### Configuración (opcional)

```bash
php artisan vendor:publish --tag=short-links-config
```

Claves principales en `config/short-links.php`:

| Clave | Default | Descripción |
|-------|---------|-------------|
| `route_prefix` | `l` | Prefijo de rutas públicas |
| `qr_generator` | `RichardRoman\ShortLinks\Laravel\Qr\EndroidQrGenerator::class` | Clase del generador de QR a resolver desde el contenedor |
| `throttle` | `120,1` | Rate limit redirect/QR |
| `cache.ttl` | `3600` | TTL cache de URL resuelta (seg) |
| `cache.prefix` | `short_link_redirect:` | Prefijo clave cache |
| `generator.length` | `8` | Longitud de códigos auto-generados |
| `generator.charset` | charset sin ambiguos | Caracteres del generador aleatorio |
| `route_pattern` | `[a-hjkmnp-z2-9]{8}` | Regex Laravel `where()` + validación de códigos |

Variables de entorno opcionales: `SHORT_LINKS_LENGTH`, `SHORT_LINKS_CHARSET`, `SHORT_LINKS_ROUTE_PATTERN`.

## v1.1.0 — Códigos configurables y manuales

Actualización **semver minor** compatible con v1.0: sin overrides en `.env`, el comportamiento es idéntico a 1.0.0.

### Código manual opcional

```php
$shortLink = ShortLinks::create(
    urlDestino: 'https://example.com/recurso',
    titulo: 'Demo',
    codigo: 'K7MNP2WX', // normalizado a minúsculas; debe cumplir route_pattern
);
```

Si el código no cumple `route_pattern`, se lanza `InvalidCodeFormatException`. Si ya existe, `DuplicateCodeException` sin reintentos aleatorios.

### Personalizar generador y patrón de ruta

```env
SHORT_LINKS_LENGTH=5
SHORT_LINKS_CHARSET=abc
SHORT_LINKS_ROUTE_PATTERN=[abc]{5}
```

El generador y las rutas públicas MUST usar el mismo `route_pattern`.

### Migración incremental (instalaciones existentes)

Tras `composer update` a `^1.1`, ejecutar:

```bash
php artisan migrate
```

Aplica la ampliación de `codigo` a 64 caracteres sin truncar códigos existentes. Instalaciones nuevas ya crean la columna con longitud 64.

### Actualizar desde v1.0

```bash
composer require richard-roman/short-links-qr:^1.1
php artisan migrate
```

Ver [CHANGELOG.md](CHANGELOG.md) para el detalle completo.

## Uso básico

### Crear short links (Facade)

```php
use RichardRoman\ShortLinks\Laravel\Facades\ShortLinks;

$shortLink = ShortLinks::create(
    urlDestino: 'https://www.youtube.com/watch?v=abc123',
    titulo: 'Video demo',
    entidadTipo: 'entregable',
    entidadId: $entregableId,
    creadoPorId: auth()->id(),
);

$existente = ShortLinks::findByEntity('entregable', $entregableId);
```

### Rutas públicas (auto-registradas)

- `GET /l/{codigo}` → redirect 302 seguro + analytics
- `GET /l/{codigo}/qr` → PNG 300×300 del short URL

Nombres de ruta: `short-links.redirect`, `short-links.qr`.

**Importante:** registrar el ServiceProvider antes de rutas catch-all del host (p. ej. `/blog`).

### Entity resolvers (host)

El paquete no conoce modelos de la app. Registrar resolvers en `AppServiceProvider::boot()`:

```php
use App\ShortLinks\Resolvers\EntregableEntityResolver;
use App\ShortLinks\Resolvers\ProyectoEntityResolver;

$this->app->tag([
    ProyectoEntityResolver::class,
    EntregableEntityResolver::class,
], 'short-links.entity-resolvers');
```

Cada resolver implementa `RichardRoman\ShortLinks\Contracts\EntityResolverInterface`:

```php
public function supports(string $entidadTipo): bool;
public function resolveUrl(string $entidadId): ?string;
```

### Cache de redirect

Tras resolver la URL (resolver host o `url_destino`), se cachea por `codigo`. La cache se invalida al desactivar un link.

### QR

Requiere `endroid/qr-code` en el proyecto consumidor. Sin la dependencia, `GET /l/{codigo}/qr` responde 503.

## Ejemplo multi-app

App A (IoT-FISI): resolvers `proyecto`, `componente`, `entregable`.  
App B (blog): resolver `articulo` opcional; links externos solo con `url_destino`.

Misma instalación Composer; distintos resolvers taggeados por host.

## Desarrollo local

```bash
git clone https://github.com/Richard-Roman/short-links-qr.git
cd short-links-qr
composer install
composer validate --strict
composer test
```

### Path repository (monorepo dev)

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../short-links-qr",
            "options": { "symlink": true }
        }
    ],
    "require": {
        "richard-roman/short-links-qr": "@dev"
    }
}
```

## Estructura

```
src/Contracts/     ← interfaces
src/Core/          ← PHP puro (servicios, VOs, validación)
src/Laravel/       ← Eloquent, rutas, Facade, QR endroid
tests/Unit/        ← tests sin framework
tests/Feature/     ← Orchestra Testbench
```

## Licencia

MIT — ver [LICENSE](LICENSE).
