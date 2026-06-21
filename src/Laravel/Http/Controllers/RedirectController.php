<?php

namespace RichardRoman\ShortLinks\Laravel\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RichardRoman\ShortLinks\Core\Services\RedirectService;
use RichardRoman\ShortLinks\Laravel\Actions\RecordClickAction;

final class RedirectController
{
    public function __invoke(
        string $codigo,
        Request $request,
        RedirectService $redirectService,
        RecordClickAction $recordClick,
    ): RedirectResponse {
        $shortLink = $redirectService->resolve($codigo);

        if ($shortLink === null) {
            abort(404);
        }

        $recordClick->execute($shortLink, $request);

        return redirect()->away($shortLink->urlDestino, 302);
    }
}
