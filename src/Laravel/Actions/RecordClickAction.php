<?php

namespace RichardRoman\ShortLinks\Laravel\Actions;

use Illuminate\Http\Request;
use RichardRoman\ShortLinks\Contracts\ShortLinkRepositoryInterface;
use RichardRoman\ShortLinks\Core\Data\ClickData;
use RichardRoman\ShortLinks\Core\ValueObjects\ShortLink;

final class RecordClickAction
{
    public function __construct(
        private readonly ShortLinkRepositoryInterface $repository,
    ) {}

    public function execute(ShortLink $shortLink, Request $request): void
    {
        $this->repository->incrementClicksAndRecord($shortLink, new ClickData(
            ipHash: hash('sha256', $request->ip() ?? ''),
            referrer: $this->truncate($request->headers->get('referer')),
            userAgent: $this->truncate($request->userAgent()),
        ));
    }

    private function truncate(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return mb_substr($value, 0, 500);
    }
}
