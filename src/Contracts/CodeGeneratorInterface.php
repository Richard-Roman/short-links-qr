<?php

namespace RichardRoman\ShortLinks\Contracts;

interface CodeGeneratorInterface
{
    public function generate(): string;
}
