# richard-roman/short-links-qr

Paquete Composer de short links + códigos QR para aplicaciones Laravel.

**Estado:** Fase 3 — core + adaptador Laravel + QR  
**Requisitos:** PHP ^8.3, Laravel ^11|^12|^13, PostgreSQL 14+ (adaptador en producción)  
**Repositorio:** [github.com/Richard-Roman/short-links-qr](https://github.com/Richard-Roman/short-links-qr)

## Instalación

```bash
composer require richard-roman/short-links-qr
composer require endroid/qr-code:^6.0
```

Publicar configuración (opcional):

```bash
php artisan vendor:publish --tag=short-links-config
```

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

### Entity resolvers (host)

Registrar resolvers en el host con tag DI:

```php
$this->app->tag([
    App\ShortLinks\Resolvers\ProyectoEntityResolver::class,
    App\ShortLinks\Resolvers\EntregableEntityResolver::class,
], 'short-links.entity-resolvers');
```

Cada resolver implementa `RichardRoman\ShortLinks\Contracts\EntityResolverInterface`.

### Cache de redirect

Configuración en `config/short-links.php`:

```php
'cache' => [
    'ttl' => 3600,
    'prefix' => 'short_link_redirect:',
],
```

La cache se invalida al desactivar un link vía `EloquentShortLinkRepository::deactivateByCodigo()`.

## Desarrollo local

```bash
git clone https://github.com/Richard-Roman/short-links-qr.git
cd short-links-qr
composer install
composer validate --strict
composer test
```

### Consumir desde `web-iot-fisi` (path repository)

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
        "richard-roman/short-links-qr": "@dev",
        "endroid/qr-code": "^6.0"
    }
}
```

```bash
composer require richard-roman/short-links-qr:@dev
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
