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
}
