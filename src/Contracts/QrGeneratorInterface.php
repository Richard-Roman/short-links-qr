<?php

namespace RichardRoman\ShortLinks\Contracts;

interface QrGeneratorInterface
{
    public function generatePng(string $shortUrl): string;
}
