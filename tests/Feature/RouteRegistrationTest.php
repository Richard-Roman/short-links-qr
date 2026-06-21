<?php

namespace RichardRoman\ShortLinks\Tests\Feature;

use Illuminate\Support\Facades\Route;
use RichardRoman\ShortLinks\Laravel\Models\ShortLink;
use RichardRoman\ShortLinks\Tests\TestCase;

final class RouteRegistrationTest extends TestCase
{
    public function test_short_link_routes_are_registered(): void
    {
        $this->assertTrue(Route::has('short-links.redirect'));
        $this->assertTrue(Route::has('short-links.qr'));
    }

    public function test_invalid_codigo_pattern_returns_not_found(): void
    {
        ShortLink::factory()->create([
            'codigo' => 'abcdefgh',
            'url_destino' => 'https://example.com/valid',
        ]);

        $this->get('/l/abcdefg')->assertNotFound();
        $this->get('/l/abcdefg0')->assertNotFound();
    }
}
