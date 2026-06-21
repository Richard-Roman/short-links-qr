<?php

namespace RichardRoman\ShortLinks\Core\Cache;

use RichardRoman\ShortLinks\Contracts\RedirectCacheInterface;

final class InMemoryRedirectCache implements RedirectCacheInterface
{
    /** @var array<string, string> */
    private array $entries = [];

    public function get(string $codigo): ?string
    {
        return $this->entries[$codigo] ?? null;
    }

    public function put(string $codigo, string $urlDestino): void
    {
        $this->entries[$codigo] = $urlDestino;
    }

    public function forget(string $codigo): void
    {
        unset($this->entries[$codigo]);
    }
}
