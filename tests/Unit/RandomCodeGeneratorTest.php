<?php

namespace RichardRoman\ShortLinks\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RichardRoman\ShortLinks\Core\Generators\RandomCodeGenerator;

final class RandomCodeGeneratorTest extends TestCase
{
    public function test_generates_eight_characters(): void
    {
        $generator = new RandomCodeGenerator();

        $this->assertSame(8, strlen($generator->generate()));
    }

    public function test_generates_only_allowed_charset(): void
    {
        $generator = new RandomCodeGenerator();
        $pattern = '/^[a-hjkmnp-z2-9]{8}$/';

        for ($i = 0; $i < 50; $i++) {
            $this->assertMatchesRegularExpression($pattern, $generator->generate());
        }
    }
}
