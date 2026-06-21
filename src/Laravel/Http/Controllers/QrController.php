<?php

namespace RichardRoman\ShortLinks\Laravel\Http\Controllers;

use Illuminate\Http\Response;
use RichardRoman\ShortLinks\Contracts\ShortLinkRepositoryInterface;
use RichardRoman\ShortLinks\Core\Exceptions\QrGeneratorNotAvailableException;
use RichardRoman\ShortLinks\Core\Services\QrService;

final class QrController
{
    public function __invoke(
        string $codigo,
        ShortLinkRepositoryInterface $repository,
        QrService $qrService,
    ): Response {
        $shortLink = $repository->findActiveByCodigo($codigo);

        if ($shortLink === null) {
            abort(404);
        }

        try {
            $png = $qrService->generatePng(route('short-links.redirect', ['codigo' => $codigo]));
        } catch (QrGeneratorNotAvailableException) {
            abort(503, QrGeneratorNotAvailableException::missingDependency()->getMessage());
        }

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="qr-' . $codigo . '.png"',
        ]);
    }
}
