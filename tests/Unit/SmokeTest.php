<?php

namespace RichardRoman\ShortLinks\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RichardRoman\ShortLinks\Laravel\ShortLinksServiceProvider;

class SmokeTest extends TestCase
{
    public function test_package_autoloads(): void
    {
        $this->assertTrue(class_exists(ShortLinksServiceProvider::class));
    }
}
