<?php

namespace RichardRoman\ShortLinks\Tests\Feature;

use Illuminate\Support\Facades\Cache;
use RichardRoman\ShortLinks\Contracts\EntityResolverRegistryInterface;
use RichardRoman\ShortLinks\Contracts\QrGeneratorInterface;
use RichardRoman\ShortLinks\Core\Exceptions\QrGeneratorNotAvailableException;
use RichardRoman\ShortLinks\Core\Resolvers\EntityResolverRegistry;
use RichardRoman\ShortLinks\Laravel\Models\ShortLink;
use RichardRoman\ShortLinks\Tests\Support\CountingEntityResolver;
use RichardRoman\ShortLinks\Tests\TestCase;

final class RedirectCacheTest extends TestCase
{
    public function test_redirect_stores_resolved_url_in_cache(): void
    {
        ShortLink::factory()->create([
            'codigo' => 'cache456',
            'url_destino' => 'https://example.com/cached-target',
        ]);

        $this->get('/l/cache456')->assertRedirect('https://example.com/cached-target');

        $this->assertSame(
            'https://example.com/cached-target',
            Cache::get((string) config('short-links.cache.prefix') . 'cache456'),
        );
    }

    public function test_second_redirect_does_not_call_entity_resolver_again(): void
    {
        $resolver = new CountingEntityResolver('proyecto', 'https://example.com/public/project/1');

        $this->app->singleton(
            EntityResolverRegistryInterface::class,
            static fn (): EntityResolverRegistry => new EntityResolverRegistry([$resolver]),
        );
        $this->app->forgetInstance(\RichardRoman\ShortLinks\Core\Services\RedirectService::class);

        ShortLink::factory()->create([
            'codigo' => 'cache789',
            'url_destino' => 'https://example.com/fallback',
            'entidad_tipo' => 'proyecto',
            'entidad_id' => '11111111-1111-1111-1111-111111111111',
        ]);

        $this->get('/l/cache789')->assertRedirect('https://example.com/public/project/1');
        $this->get('/l/cache789')->assertRedirect('https://example.com/public/project/1');

        $this->assertSame(1, $resolver->calls);
    }
}

final class QrGeneratorUnavailableTest extends TestCase
{
    public function test_qr_returns_service_unavailable_without_generator(): void
    {
        $this->app->instance(QrGeneratorInterface::class, new class implements QrGeneratorInterface
        {
            public function generatePng(string $shortUrl): string
            {
                throw QrGeneratorNotAvailableException::missingDependency();
            }
        });

        $shortLink = ShortLink::factory()->create([
            'codigo' => 'qrnopng1',
        ]);

        $this->get('/l/' . $shortLink->codigo . '/qr')->assertStatus(503);
    }
}
