<?php

namespace RichardRoman\ShortLinks\Laravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RichardRoman\ShortLinks\Contracts\ShortLinkRepositoryInterface;
use RichardRoman\ShortLinks\Core\Data\ClickData;
use RichardRoman\ShortLinks\Core\ValueObjects\ShortLink;

final class ProcessShortLinkClickJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly ShortLink $shortLink,
        public readonly string $ipHash,
        public readonly ?string $referrer,
        public readonly ?string $userAgent,
    ) {}

    public function handle(ShortLinkRepositoryInterface $repository): void
    {
        $repository->incrementClicksAndRecord(
            $this->shortLink,
            new ClickData(
                ipHash: $this->ipHash,
                referrer: $this->referrer,
                userAgent: $this->userAgent,
            )
        );
    }
}
