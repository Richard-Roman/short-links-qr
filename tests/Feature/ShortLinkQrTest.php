<?php

namespace RichardRoman\ShortLinks\Tests\Feature;

use RichardRoman\ShortLinks\Laravel\Models\ShortLink;
use RichardRoman\ShortLinks\Tests\TestCase;

final class ShortLinkQrTest extends TestCase
{
    public function test_qr_returns_png_for_active_link(): void
    {
        $shortLink = ShortLink::factory()->create([
            'codigo' => 'k7mnp2wx',
        ]);

        $response = $this->get('/l/' . $shortLink->codigo . '/qr');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
    }

    public function test_qr_returns_404_for_inactive_link(): void
    {
        $shortLink = ShortLink::factory()->inactive()->create([
            'codigo' => 'abcdefgh',
        ]);

        $this->get('/l/' . $shortLink->codigo . '/qr')->assertNotFound();
    }
}
