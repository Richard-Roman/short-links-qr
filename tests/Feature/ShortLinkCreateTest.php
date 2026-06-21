<?php

namespace RichardRoman\ShortLinks\Tests\Feature;

use RichardRoman\ShortLinks\Core\Exceptions\InvalidUrlException;
use RichardRoman\ShortLinks\Core\Services\ShortLinkService;
use RichardRoman\ShortLinks\Laravel\Models\ShortLink;
use RichardRoman\ShortLinks\Tests\TestCase;

final class ShortLinkCreateTest extends TestCase
{
    public function test_create_rejects_javascript_url(): void
    {
        $this->expectException(InvalidUrlException::class);

        $this->app->make(ShortLinkService::class)->create('javascript:alert(1)');

        $this->assertDatabaseCount('short_links', 0);
    }

    public function test_create_persists_valid_https_link(): void
    {
        $shortLink = $this->app->make(ShortLinkService::class)->create(
            'https://drive.google.com/file/d/xyz/view',
            titulo: 'Video demo',
        );

        $this->assertMatchesRegularExpression('/^[a-hjkmnp-z2-9]{8}$/', $shortLink->codigo);
        $this->assertDatabaseHas('short_links', [
            'id' => $shortLink->id,
            'url_destino' => 'https://drive.google.com/file/d/xyz/view',
            'titulo' => 'Video demo',
            'activo' => true,
        ]);
    }

    public function test_create_with_default_config_maintains_v1_parity(): void
    {
        $this->assertSame(8, config('short-links.generator.length'));
        $this->assertSame(
            'abcdefghjkmnpqrstuvwxyz23456789',
            config('short-links.generator.charset'),
        );
        $this->assertSame('[a-hjkmnp-z2-9]{8}', config('short-links.route_pattern'));

        $shortLink = $this->app->make(ShortLinkService::class)->create(
            'https://example.com/v1-parity',
        );

        $this->assertSame(8, strlen($shortLink->codigo));
        $this->assertMatchesRegularExpression('/^[a-hjkmnp-z2-9]{8}$/', $shortLink->codigo);
        $this->assertDatabaseHas('short_links', [
            'id' => $shortLink->id,
            'codigo' => $shortLink->codigo,
            'url_destino' => 'https://example.com/v1-parity',
        ]);
    }

    public function test_create_persists_manual_code_up_to_64_characters(): void
    {
        config(['short-links.route_pattern' => '[a-z0-9-]{64}']);
        $this->app->forgetInstance(ShortLinkService::class);

        $longCode = 'this-is-a-very-long-custom-code-that-has-exactly-64-chars-length';
        $this->assertSame(64, strlen($longCode));

        $shortLink = $this->app->make(ShortLinkService::class)->create(
            'https://example.com/long-url',
            titulo: 'Long Code Demo',
            codigo: $longCode,
        );

        $this->assertSame($longCode, $shortLink->codigo);
        $this->assertDatabaseHas('short_links', [
            'id' => $shortLink->id,
            'codigo' => $longCode,
            'url_destino' => 'https://example.com/long-url',
            'titulo' => 'Long Code Demo',
            'activo' => true,
        ]);
    }
}
