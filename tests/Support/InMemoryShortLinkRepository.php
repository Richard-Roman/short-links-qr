<?php

namespace RichardRoman\ShortLinks\Tests\Support;

use RichardRoman\ShortLinks\Contracts\ShortLinkRepositoryInterface;
use RichardRoman\ShortLinks\Core\Data\ClickData;
use RichardRoman\ShortLinks\Core\Data\CreateShortLinkData;
use RichardRoman\ShortLinks\Core\Exceptions\DuplicateCodeException;
use RichardRoman\ShortLinks\Core\ValueObjects\ShortLink;

final class InMemoryShortLinkRepository implements ShortLinkRepositoryInterface
{
    /** @var array<string, ShortLink> */
    private array $byCodigo = [];

    /** @var array<string, ShortLink> */
    private array $byEntity = [];

    /** @var list<string> */
    private array $duplicateCodigos = [];

    private int $createAttempts = 0;

    public function seed(ShortLink $shortLink): void
    {
        $this->byCodigo[$shortLink->codigo] = $shortLink;

        if ($shortLink->entidadTipo !== null && $shortLink->entidadId !== null) {
            $this->byEntity[$this->entityKey($shortLink->entidadTipo, $shortLink->entidadId)] = $shortLink;
        }
    }

    public function rejectCodigoOnce(string $codigo): void
    {
        $this->duplicateCodigos[] = $codigo;
    }

    public function rejectNextCreates(int $times): void
    {
        for ($i = 0; $i < $times; $i++) {
            $this->duplicateCodigos[] = '__any__';
        }
    }

    public function getCreateAttempts(): int
    {
        return $this->createAttempts;
    }

    public function findActiveByCodigo(string $codigo): ?ShortLink
    {
        $shortLink = $this->byCodigo[$codigo] ?? null;

        if ($shortLink === null || ! $shortLink->activo) {
            return null;
        }

        return $shortLink;
    }

    public function findActiveByEntity(string $entidadTipo, string $entidadId): ?ShortLink
    {
        $shortLink = $this->byEntity[$this->entityKey($entidadTipo, $entidadId)] ?? null;

        if ($shortLink === null || ! $shortLink->activo) {
            return null;
        }

        return $shortLink;
    }

    public function create(CreateShortLinkData $data): ShortLink
    {
        $this->createAttempts++;

        if ($this->shouldRejectCodigo($data->codigo)) {
            throw DuplicateCodeException::forCodigo($data->codigo);
        }

        if (isset($this->byCodigo[$data->codigo])) {
            throw DuplicateCodeException::forCodigo($data->codigo);
        }

        $shortLink = new ShortLink(
            id: bin2hex(random_bytes(16)),
            codigo: $data->codigo,
            urlDestino: $data->urlDestino,
            entidadTipo: $data->entidadTipo,
            entidadId: $data->entidadId,
            titulo: $data->titulo,
            creadoPorId: $data->creadoPorId,
        );

        $this->seed($shortLink);

        return $shortLink;
    }

    public function incrementClicksAndRecord(ShortLink $shortLink, ClickData $clickData): void
    {
        $updated = new ShortLink(
            id: $shortLink->id,
            codigo: $shortLink->codigo,
            urlDestino: $shortLink->urlDestino,
            entidadTipo: $shortLink->entidadTipo,
            entidadId: $shortLink->entidadId,
            titulo: $shortLink->titulo,
            creadoPorId: $shortLink->creadoPorId,
            activo: $shortLink->activo,
            totalClicks: $shortLink->totalClicks + 1,
            qrStorageUrl: $shortLink->qrStorageUrl,
        );

        $this->seed($updated);
    }

    public function deactivateByCodigo(string $codigo): void
    {
        if (! isset($this->byCodigo[$codigo])) {
            return;
        }

        $existing = $this->byCodigo[$codigo];

        $deactivated = new ShortLink(
            id: $existing->id,
            codigo: $existing->codigo,
            urlDestino: $existing->urlDestino,
            entidadTipo: $existing->entidadTipo,
            entidadId: $existing->entidadId,
            titulo: $existing->titulo,
            creadoPorId: $existing->creadoPorId,
            activo: false,
            totalClicks: $existing->totalClicks,
            qrStorageUrl: $existing->qrStorageUrl,
        );

        $this->byCodigo[$codigo] = $deactivated;
        if ($existing->entidadTipo !== null && $existing->entidadId !== null) {
            $this->byEntity[$this->entityKey($existing->entidadTipo, $existing->entidadId)] = $deactivated;
        }
    }

    private function shouldRejectCodigo(string $codigo): bool
    {
        if ($this->duplicateCodigos === []) {
            return false;
        }

        $next = array_shift($this->duplicateCodigos);

        return $next === '__any__' || $next === $codigo;
    }

    private function entityKey(string $entidadTipo, string $entidadId): string
    {
        return $entidadTipo . ':' . $entidadId;
    }
}
