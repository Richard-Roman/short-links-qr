<?php

namespace RichardRoman\ShortLinks\Core\Exceptions;

use RuntimeException;

final class CodeGenerationFailedException extends RuntimeException
{
    public static function maxAttemptsReached(int $maxAttempts): self
    {
        return new self("No se pudo generar un código único tras {$maxAttempts} intentos.");
    }
}
