<?php

namespace RichardRoman\ShortLinks\Laravel\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RichardRoman\ShortLinks\Contracts\ShortLinkRepositoryInterface;
use RichardRoman\ShortLinks\Core\Services\RedirectService;
use RichardRoman\ShortLinks\Laravel\Actions\RecordClickAction;

final class RedirectController
{
    public function __invoke(
        string $codigo,
        Request $request,
        RedirectService $redirectService,
        ShortLinkRepositoryInterface $repository,
        RecordClickAction $recordClick,
    ): RedirectResponse {
        $urlDestino = $redirectService->resolve($codigo);

        if ($urlDestino === null) {
            abort(404);
        }

        $shortLink = $repository->findActiveByCodigo($codigo);

        if ($shortLink === null) {
            abort(404);
        }

        $recordClick->execute($shortLink, $request);

        return redirect()->away($urlDestino, 302);
    }
}
