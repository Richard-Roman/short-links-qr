<?php

namespace RichardRoman\ShortLinks\Core\Services;

use RichardRoman\ShortLinks\Contracts\QrGeneratorInterface;

final class QrService
{
    public function __construct(
        private readonly QrGeneratorInterface $qrGenerator,
    ) {}

    public function generatePng(string $absoluteShortUrl): string
    {
        return $this->qrGenerator->generatePng($absoluteShortUrl);
    }
}
