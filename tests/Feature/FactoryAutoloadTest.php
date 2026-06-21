<?php

namespace RichardRoman\ShortLinks\Tests\Feature;

use RichardRoman\ShortLinks\Database\Factories\ShortLinkFactory;
use RichardRoman\ShortLinks\Laravel\Models\ShortLink;
use RichardRoman\ShortLinks\Tests\TestCase;

final class FactoryAutoloadTest extends TestCase
{
    public function test_factory_can_be_resolved_and_instantiated(): void
    {
        $factory = ShortLink::factory();

        $this->assertInstanceOf(ShortLinkFactory::class, $factory);

        $shortLink = $factory->make();
        $this->assertInstanceOf(ShortLink::class, $shortLink);
    }
}
