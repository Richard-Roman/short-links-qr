<?php

namespace RichardRoman\ShortLinks\Core\Exceptions;

use InvalidArgumentException;

final class InvalidCodeFormatException extends InvalidArgumentException
{
    public static function forCodigo(string $codigo, string $pattern): self
    {
        return new self("El código '{$codigo}' no cumple con el formato requerido: {$pattern}.");
    }
}
