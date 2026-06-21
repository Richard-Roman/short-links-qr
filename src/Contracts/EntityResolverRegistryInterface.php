<?php

namespace RichardRoman\ShortLinks\Contracts;

interface EntityResolverRegistryInterface
{
    public function resolveUrl(string $entidadTipo, string $entidadId): ?string;
}
