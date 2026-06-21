<?php

namespace RichardRoman\ShortLinks\Laravel\Http\Controllers;

use RichardRoman\ShortLinks\Contracts\ShortLinkRepositoryInterface;

final class QrController
{
    public function __invoke(string $codigo, ShortLinkRepositoryInterface $repository): void
    {
        $shortLink = $repository->findActiveByCodigo($codigo);

        if ($shortLink === null) {
            abort(404);
        }

        abort(404);
    }
}
