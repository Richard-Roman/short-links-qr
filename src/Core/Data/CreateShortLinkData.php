<?php

namespace RichardRoman\ShortLinks\Core\Data;

final readonly class CreateShortLinkData
{
    public function __construct(
        public string $codigo,
        public string $urlDestino,
        public ?string $titulo = null,
        public ?string $entidadTipo = null,
        public ?string $entidadId = null,
        public ?string $creadoPorId = null,
    ) {}
}
