<?php

namespace RichardRoman\ShortLinks\Core\Exceptions;

use RuntimeException;

final class DuplicateCodeException extends RuntimeException
{
    public static function forCodigo(string $codigo): self
    {
        return new self("El código '{$codigo}' ya existe.");
    }
}
