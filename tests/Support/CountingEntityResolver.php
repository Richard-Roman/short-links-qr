<?php

namespace RichardRoman\ShortLinks\Tests\Support;

use RichardRoman\ShortLinks\Contracts\EntityResolverInterface;

final class CountingEntityResolver implements EntityResolverInterface
{
    public int $calls = 0;

    public function __construct(
        private readonly string $entidadTipo,
        private readonly string $resolvedUrl,
    ) {}

    public function supports(string $entidadTipo): bool
    {
        return $entidadTipo === $this->entidadTipo;
    }

    public function resolveUrl(string $entidadId): ?string
    {
        $this->calls++;

        return $this->resolvedUrl;
    }
}
