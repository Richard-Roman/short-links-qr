<?php

namespace RichardRoman\ShortLinks\Core\Generators;

use RichardRoman\ShortLinks\Contracts\CodeGeneratorInterface;

final class RandomCodeGenerator implements CodeGeneratorInterface
{
    private readonly string $charset;

    private readonly int $length;

    public function __construct(
        string $charset = 'abcdefghjkmnpqrstuvwxyz23456789',
        int $length = 8,
    ) {
        $this->charset = $charset;
        $this->length = $length;
    }

    public function generate(): string
    {
        $codigo = '';
        $charsetLength = strlen($this->charset);

        for ($i = 0; $i < $this->length; $i++) {
            $byte = ord(random_bytes(1));
            $codigo .= $this->charset[$byte % $charsetLength];
        }

        return $codigo;
    }
}
