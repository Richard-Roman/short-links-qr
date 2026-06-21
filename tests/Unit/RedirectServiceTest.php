<?php

namespace RichardRoman\ShortLinks\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RichardRoman\ShortLinks\Core\Cache\InMemoryRedirectCache;
use RichardRoman\ShortLinks\Core\Resolvers\EntityResolverRegistry;
use RichardRoman\ShortLinks\Core\Services\RedirectService;
use RichardRoman\ShortLinks\Core\Validators\HttpUrlValidator;
use RichardRoman\ShortLinks\Core\ValueObjects\ShortLink;
use RichardRoman\ShortLinks\Tests\Support\InMemoryShortLinkRepository;
use RichardRoman\ShortLinks\Tests\Support\StubEntityResolver;

final class RedirectServiceTest extends TestCase
{
    public function test_second_resolve_uses_cache_after_first_miss(): void
    {
        $repository = new InMemoryShortLinkRepository();
        $repository->seed(new ShortLink(
            id: 'link-1',
            codigo: 'cached01',
            urlDestino: 'https://example.com/cached',
        ));

        $cache = new InMemoryRedirectCache();
        $service = new RedirectService(
            repository: $repository,
            urlValidator: new HttpUrlValidator(),
            entityResolverRegistry: new EntityResolverRegistry([]),
            redirectCache: $cache,
        );

        $first = $service->resolve('cached01');
        $second = $service->resolve('cached01');

        $this->assertInstanceOf(ShortLink::class, $first);
        $this->assertSame('https://example.com/cached', $first->urlDestino);
        $this->assertInstanceOf(ShortLink::class, $second);
        $this->assertSame($first->urlDestino, $second->urlDestino);
        $this->assertSame('https://example.com/cached', $cache->get('cached01'));
    }

    public function test_resolves_via_entity_registry(): void
    {
        $repository = new InMemoryShortLinkRepository();
        $repository->seed(new ShortLink(
            id: 'link-2',
            codigo: 'entity01',
            urlDestino: 'https://example.com/stored',
            entidadTipo: 'proyecto',
            entidadId: 'proj-1',
        ));

        $service = new RedirectService(
            repository: $repository,
            urlValidator: new HttpUrlValidator(),
            entityResolverRegistry: new EntityResolverRegistry([
                new StubEntityResolver('proyecto', 'https://example.com/public/proyecto/proj-1'),
            ]),
            redirectCache: new InMemoryRedirectCache(),
        );

        $result = $service->resolve('entity01');

        $this->assertInstanceOf(ShortLink::class, $result);
        $this->assertSame('https://example.com/public/proyecto/proj-1', $result->urlDestino);
    }

    public function test_falls_back_to_stored_url_when_resolver_returns_null(): void
    {
        $repository = new InMemoryShortLinkRepository();
        $repository->seed(new ShortLink(
            id: 'link-3',
            codigo: 'fallback',
            urlDestino: 'https://example.com/fallback',
            entidadTipo: 'entregable',
            entidadId: 'ent-9',
        ));

        $service = new RedirectService(
            repository: $repository,
            urlValidator: new HttpUrlValidator(),
            entityResolverRegistry: new EntityResolverRegistry([
                new StubEntityResolver('entregable', null),
            ]),
            redirectCache: new InMemoryRedirectCache(),
        );

        $result = $service->resolve('fallback');

        $this->assertInstanceOf(ShortLink::class, $result);
        $this->assertSame('https://example.com/fallback', $result->urlDestino);
    }

    public function test_returns_null_for_inactive_link(): void
    {
        $repository = new InMemoryShortLinkRepository();
        $repository->seed(new ShortLink(
            id: 'link-4',
            codigo: 'inactive',
            urlDestino: 'https://example.com/inactive',
            activo: false,
        ));

        $service = new RedirectService(
            repository: $repository,
            urlValidator: new HttpUrlValidator(),
            entityResolverRegistry: new EntityResolverRegistry([]),
            redirectCache: new InMemoryRedirectCache(),
        );

        $this->assertNull($service->resolve('inactive'));
    }

    public function test_returns_null_for_javascript_stored_url(): void
    {
        $repository = new InMemoryShortLinkRepository();
        $repository->seed(new ShortLink(
            id: 'link-5',
            codigo: 'badurl01',
            urlDestino: 'javascript:alert(1)',
        ));

        $service = new RedirectService(
            repository: $repository,
            urlValidator: new HttpUrlValidator(),
            entityResolverRegistry: new EntityResolverRegistry([]),
            redirectCache: new InMemoryRedirectCache(),
        );

        $this->assertNull($service->resolve('badurl01'));
    }

    public function test_invalidate_cache_forgets_entry(): void
    {
        $cache = new InMemoryRedirectCache();
        $cache->put('forgetme', 'https://example.com/forgotten');

        $service = new RedirectService(
            repository: new InMemoryShortLinkRepository(),
            urlValidator: new HttpUrlValidator(),
            entityResolverRegistry: new EntityResolverRegistry([]),
            redirectCache: $cache,
        );

        $service->invalidateCache('forgetme');

        $this->assertNull($cache->get('forgetme'));
    }
}
