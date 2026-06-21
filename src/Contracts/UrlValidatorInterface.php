<?php

namespace RichardRoman\ShortLinks\Contracts;

interface UrlValidatorInterface
{
    public function validate(?string $url): ?string;
}
