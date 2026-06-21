<?php

namespace RichardRoman\ShortLinks\Core\Services;

use RichardRoman\ShortLinks\Contracts\CodeGeneratorInterface;
use RichardRoman\ShortLinks\Contracts\ShortLinkRepositoryInterface;
use RichardRoman\ShortLinks\Contracts\UrlValidatorInterface;
use RichardRoman\ShortLinks\Core\Data\CreateShortLinkData;
use RichardRoman\ShortLinks\Core\Exceptions\CodeGenerationFailedException;
use RichardRoman\ShortLinks\Core\Exceptions\DuplicateCodeException;
use RichardRoman\ShortLinks\Core\Exceptions\InvalidUrlException;
use RichardRoman\ShortLinks\Core\ValueObjects\ShortLink;

final class ShortLinkService
{
    private const int MAX_CODE_ATTEMPTS = 5;

    public function __construct(
        private readonly ShortLinkRepositoryInterface $repository,
        private readonly UrlValidatorInterface $urlValidator,
        private readonly CodeGeneratorInterface $codeGenerator,
    ) {}

    public function create(
        string $urlDestino,
        ?string $titulo = null,
        ?string $entidadTipo = null,
        ?string $entidadId = null,
        ?string $creadoPorId = null,
    ): ShortLink {
        $urlSegura = $this->urlValidator->validate($urlDestino);

        if ($urlSegura === null) {
            throw InvalidUrlException::httpOrHttpsRequired();
        }

        if ($entidadTipo !== null && $entidadId !== null) {
            $existente = $this->repository->findActiveByEntity($entidadTipo, $entidadId);

            if ($existente !== null) {
                return $existente;
            }
        }

        return $this->persistWithUniqueCodigo(
            urlDestino: $urlSegura,
            titulo: $titulo,
            entidadTipo: $entidadTipo,
            entidadId: $entidadId,
            creadoPorId: $creadoPorId,
        );
    }

    private function persistWithUniqueCodigo(
        string $urlDestino,
        ?string $titulo,
        ?string $entidadTipo,
        ?string $entidadId,
        ?string $creadoPorId,
    ): ShortLink {
        for ($attempt = 1; $attempt <= self::MAX_CODE_ATTEMPTS; $attempt++) {
            try {
                return $this->repository->create(new CreateShortLinkData(
                    codigo: $this->codeGenerator->generate(),
                    urlDestino: $urlDestino,
                    titulo: $titulo,
                    entidadTipo: $entidadTipo,
                    entidadId: $entidadId,
                    creadoPorId: $creadoPorId,
                ));
            } catch (DuplicateCodeException) {
                if ($attempt === self::MAX_CODE_ATTEMPTS) {
                    throw CodeGenerationFailedException::maxAttemptsReached(self::MAX_CODE_ATTEMPTS);
                }
            }
        }

        throw CodeGenerationFailedException::maxAttemptsReached(self::MAX_CODE_ATTEMPTS);
    }
}
