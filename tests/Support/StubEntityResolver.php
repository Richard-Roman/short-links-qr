<?php

namespace RichardRoman\ShortLinks\Tests\Support;

use RichardRoman\ShortLinks\Contracts\EntityResolverInterface;

final class StubEntityResolver implements EntityResolverInterface
{
    public function __construct(
        private readonly string $entidadTipo,
        private readonly ?string $resolvedUrl,
    ) {}

    public function supports(string $entidadTipo): bool
    {
        return $entidadTipo === $this->entidadTipo;
    }

    public function resolveUrl(string $entidadId): ?string
    {
        return $this->resolvedUrl;
    }
}
