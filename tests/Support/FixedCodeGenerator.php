<?php

namespace RichardRoman\ShortLinks\Tests\Support;

use RichardRoman\ShortLinks\Contracts\CodeGeneratorInterface;

final class FixedCodeGenerator implements CodeGeneratorInterface
{
    /** @var list<string> */
    private array $codigos;

    public function __construct(string ...$codigos)
    {
        $this->codigos = array_values($codigos);
    }

    public function generate(): string
    {
        if ($this->codigos === []) {
            return 'abcdefgh';
        }

        return array_shift($this->codigos);
    }
}
