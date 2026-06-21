<?php

namespace RichardRoman\ShortLinks\Core\Exceptions;

use RuntimeException;

final class QrGeneratorNotAvailableException extends RuntimeException
{
    public static function missingDependency(): self
    {
        return new self('QR generator is not available. Install endroid/qr-code via composer require endroid/qr-code.');
    }
}
