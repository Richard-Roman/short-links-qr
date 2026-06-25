<?php

namespace RichardRoman\ShortLinks\Laravel\Actions;

use Illuminate\Http\Request;
use RichardRoman\ShortLinks\Core\ValueObjects\ShortLink;
use RichardRoman\ShortLinks\Laravel\Jobs\ProcessShortLinkClickJob;

final class RecordClickAction
{
    /**
     * Extrae telemetría HTTP y encola la persistencia del click de forma asíncrona.
     */
    public function execute(ShortLink $shortLink, Request $request): void
    {
        ProcessShortLinkClickJob::dispatch(
            $shortLink,
            hash('sha256', $request->ip() ?? ''),
            $this->truncate($request->headers->get('referer')),
            $this->truncate($request->userAgent())
        );
    }

    private function truncate(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return mb_substr($value, 0, 500);
    }
}
