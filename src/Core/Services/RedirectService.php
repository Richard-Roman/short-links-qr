<?php

namespace RichardRoman\ShortLinks\Core\Services;

use RichardRoman\ShortLinks\Contracts\EntityResolverRegistryInterface;
use RichardRoman\ShortLinks\Contracts\RedirectCacheInterface;
use RichardRoman\ShortLinks\Contracts\ShortLinkRepositoryInterface;
use RichardRoman\ShortLinks\Contracts\UrlValidatorInterface;

final class RedirectService
{
    public function __construct(
        private readonly ShortLinkRepositoryInterface $repository,
        private readonly UrlValidatorInterface $urlValidator,
        private readonly EntityResolverRegistryInterface $entityResolverRegistry,
        private readonly RedirectCacheInterface $redirectCache,
    ) {}

    public function resolve(string $codigo): ?string
    {
        $cachedUrl = $this->redirectCache->get($codigo);

        if ($cachedUrl !== null) {
            return $this->urlValidator->validate($cachedUrl);
        }

        $shortLink = $this->repository->findActiveByCodigo($codigo);

        if ($shortLink === null || ! $shortLink->activo) {
            return null;
        }

        $urlDestino = $this->resolveEntityOrStoredUrl($shortLink->entidadTipo, $shortLink->entidadId, $shortLink->urlDestino);
        $urlSegura = $this->urlValidator->validate($urlDestino);

        if ($urlSegura === null) {
            return null;
        }

        $this->redirectCache->put($codigo, $urlSegura);

        return $urlSegura;
    }

    public function invalidateCache(string $codigo): void
    {
        $this->redirectCache->forget($codigo);
    }

    private function resolveEntityOrStoredUrl(?string $entidadTipo, ?string $entidadId, string $urlDestino): ?string
    {
        if ($entidadTipo !== null && $entidadId !== null) {
            $resolvedUrl = $this->entityResolverRegistry->resolveUrl($entidadTipo, $entidadId);

            if ($resolvedUrl !== null) {
                return $resolvedUrl;
            }
        }

        return $urlDestino;
    }
}
