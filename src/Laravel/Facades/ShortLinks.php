<?php

namespace RichardRoman\ShortLinks\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use RichardRoman\ShortLinks\Core\Services\ShortLinkService;
use RichardRoman\ShortLinks\Core\ValueObjects\ShortLink;

/**
 * @method static ShortLink create(string $urlDestino, ?string $titulo = null, ?string $entidadTipo = null, ?string $entidadId = null, ?string $creadoPorId = null, ?string $codigo = null)
 * @method static ShortLink|null findByEntity(string $entidadTipo, string $entidadId)
 * @method static void deactivate(string $codigo)
 * @method static ShortLink rotate(string $codigoViejo, ?string $nuevoCodigo = null)
 */
final class ShortLinks extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ShortLinkService::class;
    }
}
