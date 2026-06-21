<?php

namespace RichardRoman\ShortLinks\Laravel\Cache;

use Illuminate\Support\Facades\Cache;
use RichardRoman\ShortLinks\Contracts\RedirectCacheInterface;

final class IlluminateRedirectCache implements RedirectCacheInterface
{
    public function get(string $codigo): ?string
    {
        $value = Cache::get($this->key($codigo));

        return is_string($value) ? $value : null;
    }

    public function put(string $codigo, string $urlDestino): void
    {
        Cache::put($this->key($codigo), $urlDestino, (int) config('short-links.cache.ttl', 3600));
    }

    public function forget(string $codigo): void
    {
        Cache::forget($this->key($codigo));
    }

    private function key(string $codigo): string
    {
        return (string) config('short-links.cache.prefix', 'short_link_redirect:') . $codigo;
    }
}
