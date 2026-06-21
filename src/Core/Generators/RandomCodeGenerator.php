<?php

namespace RichardRoman\ShortLinks\Core\Generators;

use RichardRoman\ShortLinks\Contracts\CodeGeneratorInterface;

final class RandomCodeGenerator implements CodeGeneratorInterface
{
    private const string CHARSET = 'abcdefghjkmnpqrstuvwxyz23456789';

    private const int CODE_LENGTH = 8;

    public function generate(): string
    {
        $codigo = '';

        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            $byte = ord(random_bytes(1));
            $codigo .= self::CHARSET[$byte % strlen(self::CHARSET)];
        }

        return $codigo;
    }
}
