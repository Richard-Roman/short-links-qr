<?php

namespace RichardRoman\ShortLinks\Tests\Feature;

use Illuminate\Database\QueryException;
use RichardRoman\ShortLinks\Laravel\Models\ShortLink;
use RichardRoman\ShortLinks\Tests\TestCase;

final class ShortLinkUniquenessTest extends TestCase
{
    public function test_cannot_create_multiple_active_short_links_for_same_entity(): void
    {
        $entityType = 'User';
        $entityId = '123e4567-e89b-12d3-a456-426614174000';

        ShortLink::factory()->create([
            'entidad_tipo' => $entityType,
            'entidad_id' => $entityId,
            'activo' => true,
        ]);

        $this->expectException(QueryException::class);

        ShortLink::factory()->create([
            'entidad_tipo' => $entityType,
            'entidad_id' => $entityId,
            'activo' => true,
        ]);
    }

    public function test_can_create_multiple_inactive_short_links_for_same_entity(): void
    {
        $entityType = 'User';
        $entityId = '123e4567-e89b-12d3-a456-426614174000';

        $link1 = ShortLink::factory()->create([
            'entidad_tipo' => $entityType,
            'entidad_id' => $entityId,
            'activo' => false,
        ]);

        $link2 = ShortLink::factory()->create([
            'entidad_tipo' => $entityType,
            'entidad_id' => $entityId,
            'activo' => false,
        ]);

        $this->assertDatabaseHas('short_links', ['id' => $link1->id]);
        $this->assertDatabaseHas('short_links', ['id' => $link2->id]);
    }
}
