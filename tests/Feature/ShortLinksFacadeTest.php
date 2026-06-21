<?php

namespace RichardRoman\ShortLinks\Tests\Feature;

use RichardRoman\ShortLinks\Core\Exceptions\InvalidUrlException;
use RichardRoman\ShortLinks\Laravel\Facades\ShortLinks;
use RichardRoman\ShortLinks\Laravel\Models\ShortLink;
use RichardRoman\ShortLinks\Tests\TestCase;

final class ShortLinksFacadeTest extends TestCase
{
    public function test_facade_create_persists_short_link(): void
    {
        $shortLink = ShortLinks::create(
            urlDestino: 'https://example.com/facade',
            titulo: 'Facade test',
        );

        $this->assertMatchesRegularExpression('/^[a-hjkmnp-z2-9]{8}$/', $shortLink->codigo);
        $this->assertDatabaseHas('short_links', [
            'id' => $shortLink->id,
            'url_destino' => 'https://example.com/facade',
        ]);
    }

    public function test_facade_find_by_entity_returns_existing_link(): void
    {
        $existing = ShortLink::factory()->create([
            'codigo' => 'facade01',
            'entidad_tipo' => 'entregable',
            'entidad_id' => '22222222-2222-2222-2222-222222222222',
        ]);

        $found = ShortLinks::findByEntity('entregable', '22222222-2222-2222-2222-222222222222');

        $this->assertNotNull($found);
        $this->assertSame($existing->codigo, $found->codigo);
    }

    public function test_facade_create_rejects_invalid_url(): void
    {
        $this->expectException(InvalidUrlException::class);

        ShortLinks::create('javascript:alert(1)');
    }
}
