<?php

namespace RichardRoman\ShortLinks\Core\ValueObjects;

final readonly class ShortLink
{
    public function __construct(
        public string $id,
        public string $codigo,
        public string $urlDestino,
        public ?string $entidadTipo = null,
        public ?string $entidadId = null,
        public ?string $titulo = null,
        public ?string $creadoPorId = null,
        public bool $activo = true,
        public int $totalClicks = 0,
        public ?string $qrStorageUrl = null,
    ) {}
}
