<?php

namespace RichardRoman\ShortLinks\Tests\Feature;

use RichardRoman\ShortLinks\Contracts\QrGeneratorInterface;
use RichardRoman\ShortLinks\Laravel\Qr\EndroidQrGenerator;
use RichardRoman\ShortLinks\Tests\TestCase;

final class QrGeneratorBindingTest extends TestCase
{
    public function test_resolves_default_qr_generator(): void
    {
        $generator = $this->app->make(QrGeneratorInterface::class);

        $this->assertInstanceOf(EndroidQrGenerator::class, $generator);
    }

    public function test_resolves_custom_qr_generator_from_config(): void
    {
        config(['short-links.qr_generator' => CustomTestQrGenerator::class]);
        $this->app->forgetInstance(QrGeneratorInterface::class);

        $generator = $this->app->make(QrGeneratorInterface::class);

        $this->assertInstanceOf(CustomTestQrGenerator::class, $generator);
        $this->assertSame('custom-qr-content', $generator->generatePng('https://example.com/l/abc'));
    }
}

class CustomTestQrGenerator implements QrGeneratorInterface
{
    public function generatePng(string $shortUrl): string
    {
        return 'custom-qr-content';
    }
}
