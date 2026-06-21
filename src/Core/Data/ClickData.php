<?php

namespace RichardRoman\ShortLinks\Core\Data;

final readonly class ClickData
{
    public function __construct(
        public ?string $ipHash = null,
        public ?string $referrer = null,
        public ?string $userAgent = null,
    ) {}
}
