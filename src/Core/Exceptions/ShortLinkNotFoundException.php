<?php

namespace RichardRoman\ShortLinks\Core\Exceptions;

use DomainException;

final class ShortLinkNotFoundException extends DomainException
{
    public static function forCodigo(string $codigo): self
    {
        return new self("Short link con código {$codigo} no encontrado o inactivo.");
    }
}
