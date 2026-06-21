# richard-roman/short-links-qr

Paquete Composer de short links + códigos QR para aplicaciones Laravel.

**Estado:** en desarrollo — Fase 0 bootstrap  
**Requisitos:** PHP ^8.3  
**Repositorio:** [github.com/Richard-Roman/short-links-qr](https://github.com/Richard-Roman/short-links-qr)

## Instalación (futuro)

```bash
composer require richard-roman/short-links-qr
```

Para generación de QR, instalar también la dependencia sugerida:

```bash
composer require endroid/qr-code:^6.0
```

## Desarrollo local

### Clonar e instalar

```bash
git clone https://github.com/Richard-Roman/short-links-qr.git
cd short-links-qr
composer install
composer validate --strict
composer test
```

### Consumir desde `web-iot-fisi` (path repository)

En el `composer.json` del host, añadir el repositorio path (ruta relativa desde la raíz del host):

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../short-links-qr",
            "options": {
                "symlink": true
            }
        }
    ],
    "require": {
        "richard-roman/short-links-qr": "@dev"
    }
}
```

Luego:

```bash
composer require richard-roman/short-links-qr:@dev
```

> Ajusta `"url"` si el paquete vive en otra ruta en tu máquina. Layout recomendado: `Laravel/web-iot-fisi` y `Laravel/short-links-qr` como hermanos.

## Estructura (objetivo)

```
src/Contracts/     ← interfaces
src/Core/          ← PHP puro (servicios, VOs)
src/Laravel/       ← adaptador Laravel (ServiceProvider, Eloquent, rutas)
tests/Unit/        ← tests sin framework
tests/Feature/     ← tests Orchestra Testbench (Fase 2+)
```

## Licencia

MIT — ver [LICENSE](LICENSE).
