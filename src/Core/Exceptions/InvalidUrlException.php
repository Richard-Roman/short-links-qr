<?php

namespace RichardRoman\ShortLinks\Core\Exceptions;

use InvalidArgumentException;

final class InvalidUrlException extends InvalidArgumentException
{
    public static function httpOrHttpsRequired(): self
    {
        return new self('La URL destino debe usar esquema HTTP o HTTPS.');
    }
}
