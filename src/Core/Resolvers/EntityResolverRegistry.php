<?php

namespace RichardRoman\ShortLinks\Core\Resolvers;

use RichardRoman\ShortLinks\Contracts\EntityResolverInterface;
use RichardRoman\ShortLinks\Contracts\EntityResolverRegistryInterface;

final class EntityResolverRegistry implements EntityResolverRegistryInterface
{
    /** @param iterable<EntityResolverInterface> $resolvers */
    public function __construct(
        private readonly iterable $resolvers,
    ) {}

    public function resolveUrl(string $entidadTipo, string $entidadId): ?string
    {
        foreach ($this->resolvers as $resolver) {
            if ($resolver->supports($entidadTipo)) {
                return $resolver->resolveUrl($entidadId);
            }
        }

        return null;
    }
}
