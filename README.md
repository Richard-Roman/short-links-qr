# richard-roman/short-links-qr

Paquete Composer de short links + códigos QR para aplicaciones Laravel.

**Versión:** 1.0.0  
**Requisitos:** PHP ^8.3, Laravel ^11|^12|^13  
**Repositorio:** [github.com/Richard-Roman/short-links-qr](https://github.com/Richard-Roman/short-links-qr)  
**Packagist:** [packagist.org/packages/richard-roman/short-links-qr](https://packagist.org/packages/richard-roman/short-links-qr)

## Instalación

### Packagist (recomendado)

```bash
composer require richard-roman/short-links-qr:^1.0
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
        "richard-roman/short-links-qr": "^1.0",
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
| `throttle` | `120,1` | Rate limit redirect/QR |
| `cache.ttl` | `3600` | TTL cache de URL resuelta (seg) |
| `cache.prefix` | `short_link_redirect:` | Prefijo clave cache |

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

## Registro en Packagist

1. Iniciar sesión en [packagist.org](https://packagist.org)
2. Submit package URL: `https://github.com/Richard-Roman/short-links-qr`
3. Configurar GitHub webhook (Packagist lo guía al submit)

## Licencia

MIT — ver [LICENSE](LICENSE).
