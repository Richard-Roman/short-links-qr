<?php

namespace RichardRoman\ShortLinks\Core\ValueObjects;

final readonly class ShortLinkClick
{
    public function __construct(
        public string $shortLinkId,
        public ?string $ipHash = null,
        public ?string $referrer = null,
        public ?string $userAgent = null,
    ) {}
}
