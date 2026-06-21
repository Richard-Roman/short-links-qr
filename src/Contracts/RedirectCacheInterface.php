<?php

namespace RichardRoman\ShortLinks\Contracts;

interface RedirectCacheInterface
{
    public function get(string $codigo): ?string;

    public function put(string $codigo, string $urlDestino): void;

    public function forget(string $codigo): void;
}
