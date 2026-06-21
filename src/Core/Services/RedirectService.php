<?php

namespace RichardRoman\ShortLinks\Core\Services;

use RichardRoman\ShortLinks\Contracts\EntityResolverRegistryInterface;
use RichardRoman\ShortLinks\Contracts\RedirectCacheInterface;
use RichardRoman\ShortLinks\Contracts\ShortLinkRepositoryInterface;
use RichardRoman\ShortLinks\Contracts\UrlValidatorInterface;
use RichardRoman\ShortLinks\Core\ValueObjects\ShortLink;

final class RedirectService
{
    public function __construct(
        private readonly ShortLinkRepositoryInterface $repository,
        private readonly UrlValidatorInterface $urlValidator,
        private readonly EntityResolverRegistryInterface $entityResolverRegistry,
        private readonly RedirectCacheInterface $redirectCache,
    ) {}

    public function resolve(string $codigo): ?ShortLink
    {
        $shortLink = $this->repository->findActiveByCodigo($codigo);

        if ($shortLink === null || ! $shortLink->activo) {
            return null;
        }

        $cachedUrl = $this->redirectCache->get($codigo);

        if ($cachedUrl !== null) {
            $validUrl = $this->urlValidator->validate($cachedUrl);

            if ($validUrl === null) {
                return null;
            }

            return new ShortLink(
                id: $shortLink->id,
                codigo: $shortLink->codigo,
                urlDestino: $validUrl,
                entidadTipo: $shortLink->entidadTipo,
                entidadId: $shortLink->entidadId,
                titulo: $shortLink->titulo,
                creadoPorId: $shortLink->creadoPorId,
                activo: $shortLink->activo,
                totalClicks: $shortLink->totalClicks,
                qrStorageUrl: $shortLink->qrStorageUrl,
            );
        }

        $urlDestino = $this->resolveEntityOrStoredUrl($shortLink->entidadTipo, $shortLink->entidadId, $shortLink->urlDestino);
        $urlSegura = $this->urlValidator->validate($urlDestino);

        if ($urlSegura === null) {
            return null;
        }

        $this->redirectCache->put($codigo, $urlSegura);

        return new ShortLink(
            id: $shortLink->id,
            codigo: $shortLink->codigo,
            urlDestino: $urlSegura,
            entidadTipo: $shortLink->entidadTipo,
            entidadId: $shortLink->entidadId,
            titulo: $shortLink->titulo,
            creadoPorId: $shortLink->creadoPorId,
            activo: $shortLink->activo,
            totalClicks: $shortLink->totalClicks,
            qrStorageUrl: $shortLink->qrStorageUrl,
        );
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
