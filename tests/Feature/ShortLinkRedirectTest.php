<?php

namespace RichardRoman\ShortLinks\Tests\Feature;

use RichardRoman\ShortLinks\Laravel\Models\ShortLink;
use RichardRoman\ShortLinks\Laravel\Models\ShortLinkClick;
use RichardRoman\ShortLinks\Tests\TestCase;

final class ShortLinkRedirectTest extends TestCase
{
    public function test_redirects_to_https_destination(): void
    {
        $shortLink = ShortLink::factory()->create([
            'codigo' => 'k7mnp2wx',
            'url_destino' => 'https://example.com/video',
        ]);

        $this->get('/l/' . $shortLink->codigo)
            ->assertRedirect('https://example.com/video');
    }

    public function test_returns_404_for_unknown_codigo(): void
    {
        $this->get('/l/zzzzzzzz')->assertNotFound();
    }

    public function test_returns_404_for_inactive_link(): void
    {
        $shortLink = ShortLink::factory()->inactive()->create([
            'codigo' => 'abcdefgh',
        ]);

        $this->get('/l/' . $shortLink->codigo)->assertNotFound();
    }

    public function test_does_not_redirect_javascript_destination(): void
    {
        $shortLink = ShortLink::factory()->create([
            'codigo' => 'hjkmnp23',
            'url_destino' => 'javascript:alert(1)',
        ]);

        $response = $this->get('/l/' . $shortLink->codigo);

        $response->assertNotFound();
        $response->assertHeaderMissing('Location');
    }

    public function test_records_click_with_ip_hash(): void
    {
        $shortLink = ShortLink::factory()->create([
            'codigo' => 'qrstuv23',
            'url_destino' => 'https://example.com/tracked',
            'total_clicks' => 0,
        ]);

        $this->get('/l/' . $shortLink->codigo)->assertRedirect();

        $this->assertDatabaseHas('short_links', [
            'id' => $shortLink->id,
            'total_clicks' => 1,
        ]);

        $this->assertDatabaseHas('short_link_clicks', [
            'short_link_id' => $shortLink->id,
        ]);

        $click = ShortLinkClick::query()
            ->where('short_link_id', $shortLink->id)
            ->first();

        $this->assertNotNull($click?->ip_hash);
        $this->assertSame(64, strlen((string) $click->ip_hash));
    }

    public function test_redirect_executes_only_one_select_query(): void
    {
        $shortLink = ShortLink::factory()->create([
            'codigo' => 'qrstuv24',
            'url_destino' => 'https://example.com/1query',
        ]);

        \Illuminate\Support\Facades\DB::enableQueryLog();

        $this->get('/l/' . $shortLink->codigo)->assertRedirect();

        $queries = \Illuminate\Support\Facades\DB::getQueryLog();
        $selects = array_filter($queries, fn ($q) => stripos(trim($q['query']), 'select') === 0);

        $this->assertCount(1, $selects, 'El redirect debe ejecutar exactamente un SELECT');
        
        \Illuminate\Support\Facades\DB::disableQueryLog();
    }
}
