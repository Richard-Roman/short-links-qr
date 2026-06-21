<?php

namespace RichardRoman\ShortLinks\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RichardRoman\ShortLinks\Core\Validators\HttpUrlValidator;

final class HttpUrlValidatorTest extends TestCase
{
    private HttpUrlValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new HttpUrlValidator();
    }

    public function test_rejects_javascript_url(): void
    {
        $this->assertNull($this->validator->validate('javascript:alert(1)'));
    }

    public function test_accepts_https_url(): void
    {
        $url = 'https://drive.google.com/file/d/xyz/view';

        $this->assertSame($url, $this->validator->validate($url));
    }

    public function test_rejects_empty_url(): void
    {
        $this->assertNull($this->validator->validate(''));
        $this->assertNull($this->validator->validate(null));
    }

    public function test_rejects_data_url(): void
    {
        $this->assertNull($this->validator->validate('data:text/plain,hello'));
    }
}
