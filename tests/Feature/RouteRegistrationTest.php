<?php

namespace RichardRoman\ShortLinks\Tests\Feature;

use Illuminate\Support\Facades\Route;
use RichardRoman\ShortLinks\Laravel\Models\ShortLink;
use RichardRoman\ShortLinks\Tests\TestCase;

final class RouteRegistrationTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('short-links.route_pattern', '[0-9]{4}');
    }

    public function test_short_link_routes_are_registered(): void
    {
        $this->assertTrue(Route::has('short-links.redirect'));
        $this->assertTrue(Route::has('short-links.qr'));
    }

    public function test_dynamic_route_pattern_matches_correctly(): void
    {
        ShortLink::factory()->create([
            'codigo' => '1234',
            'url_destino' => 'https://example.com/valid',
        ]);

        $this->get('/l/1234')->assertRedirect('https://example.com/valid');

        $this->get('/l/123')->assertNotFound();
        $this->get('/l/12345')->assertNotFound();
        $this->get('/l/abcd')->assertNotFound();
    }
}
