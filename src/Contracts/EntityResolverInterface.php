<?php

namespace RichardRoman\ShortLinks\Contracts;

interface EntityResolverInterface
{
    public function supports(string $entidadTipo): bool;

    public function resolveUrl(string $entidadId): ?string;
}
