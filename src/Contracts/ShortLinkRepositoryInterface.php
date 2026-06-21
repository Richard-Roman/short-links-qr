<?php

namespace RichardRoman\ShortLinks\Contracts;

use RichardRoman\ShortLinks\Core\Data\ClickData;
use RichardRoman\ShortLinks\Core\Data\CreateShortLinkData;
use RichardRoman\ShortLinks\Core\Exceptions\DuplicateCodeException;
use RichardRoman\ShortLinks\Core\ValueObjects\ShortLink;

interface ShortLinkRepositoryInterface
{
    public function findActiveByCodigo(string $codigo): ?ShortLink;

    public function findActiveByEntity(string $entidadTipo, string $entidadId): ?ShortLink;

    /** @throws DuplicateCodeException */
    public function create(CreateShortLinkData $data): ShortLink;

    public function incrementClicksAndRecord(ShortLink $shortLink, ClickData $clickData): void;
}
