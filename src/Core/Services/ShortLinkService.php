<?php

namespace RichardRoman\ShortLinks\Core\Services;

use RichardRoman\ShortLinks\Contracts\CodeGeneratorInterface;
use RichardRoman\ShortLinks\Contracts\ShortLinkRepositoryInterface;
use RichardRoman\ShortLinks\Contracts\UrlValidatorInterface;
use RichardRoman\ShortLinks\Core\Data\CreateShortLinkData;
use RichardRoman\ShortLinks\Core\Exceptions\CodeGenerationFailedException;
use RichardRoman\ShortLinks\Core\Exceptions\DuplicateCodeException;
use RichardRoman\ShortLinks\Core\Exceptions\InvalidCodeFormatException;
use RichardRoman\ShortLinks\Core\Exceptions\InvalidUrlException;
use RichardRoman\ShortLinks\Core\Exceptions\ShortLinkNotFoundException;
use RichardRoman\ShortLinks\Core\ValueObjects\ShortLink;
use Illuminate\Support\Facades\DB;

final class ShortLinkService
{
    private const int MAX_CODE_ATTEMPTS = 5;

    public function __construct(
        private readonly ShortLinkRepositoryInterface $repository,
        private readonly UrlValidatorInterface $urlValidator,
        private readonly CodeGeneratorInterface $codeGenerator,
        private readonly string $routePattern = '[a-hjkmnp-z2-9]{8}',
    ) {}

    public function create(
        string $urlDestino,
        ?string $titulo = null,
        ?string $entidadTipo = null,
        ?string $entidadId = null,
        ?string $creadoPorId = null,
        ?string $codigo = null,
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

        if ($codigo !== null) {
            $codigoNormalizado = strtolower($codigo);
            $this->assertCodigoMatchesPattern($codigoNormalizado);

            return $this->repository->create(new CreateShortLinkData(
                codigo: $codigoNormalizado,
                urlDestino: $urlSegura,
                titulo: $titulo,
                entidadTipo: $entidadTipo,
                entidadId: $entidadId,
                creadoPorId: $creadoPorId,
            ));
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
            $codigo = $this->codeGenerator->generate();

            try {
                $this->assertCodigoMatchesPattern($codigo);
            } catch (InvalidCodeFormatException) {
                if ($attempt === self::MAX_CODE_ATTEMPTS) {
                    throw CodeGenerationFailedException::maxAttemptsReached(self::MAX_CODE_ATTEMPTS);
                }

                continue;
            }

            try {
                return $this->repository->create(new CreateShortLinkData(
                    codigo: $codigo,
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

    private function assertCodigoMatchesPattern(string $codigo): void
    {
        if (! preg_match('#^' . $this->routePattern . '$#', $codigo)) {
            throw InvalidCodeFormatException::forCodigo($codigo, $this->routePattern);
        }
    }

    public function findByEntity(string $entidadTipo, string $entidadId): ?ShortLink
    {
        return $this->repository->findActiveByEntity($entidadTipo, $entidadId);
    }

    public function deactivate(string $codigo): void
    {
        $this->repository->deactivateByCodigo($codigo);
    }

    public function rotate(string $codigoViejo, ?string $nuevoCodigo = null): ShortLink
    {
        $viejo = $this->repository->findActiveByCodigo($codigoViejo);

        if ($viejo === null) {
            throw ShortLinkNotFoundException::forCodigo($codigoViejo);
        }

        return DB::transaction(function () use ($viejo, $nuevoCodigo) {
            $this->deactivate($viejo->codigo);

            return $this->create(
                urlDestino: $viejo->urlDestino,
                titulo: $viejo->titulo,
                entidadTipo: $viejo->entidadTipo,
                entidadId: $viejo->entidadId,
                creadoPorId: $viejo->creadoPorId,
                codigo: $nuevoCodigo,
            );
        });
    }
}
