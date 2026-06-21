<?php

namespace RichardRoman\ShortLinks\Laravel\Qr;

use Endroid\QrCode\Builder\Builder;
use RichardRoman\ShortLinks\Contracts\QrGeneratorInterface;
use RichardRoman\ShortLinks\Core\Exceptions\QrGeneratorNotAvailableException;

final class EndroidQrGenerator implements QrGeneratorInterface
{
    public function generatePng(string $shortUrl): string
    {
        if (! class_exists(Builder::class)) {
            throw QrGeneratorNotAvailableException::missingDependency();
        }

        $result = (new Builder(
            data: $shortUrl,
            size: 300,
        ))->build();

        return $result->getString();
    }
}
