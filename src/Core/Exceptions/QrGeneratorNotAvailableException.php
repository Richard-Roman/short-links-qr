<?php

namespace RichardRoman\ShortLinks\Core\Exceptions;

use RuntimeException;

final class QrGeneratorNotAvailableException extends RuntimeException
{
    public static function missingDependency(): self
    {
        return new self('El generador QR no está disponible. Instale endroid/qr-code.');
    }
}
