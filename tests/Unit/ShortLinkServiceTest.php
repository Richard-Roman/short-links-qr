<?php

namespace RichardRoman\ShortLinks\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RichardRoman\ShortLinks\Core\Exceptions\CodeGenerationFailedException;
use RichardRoman\ShortLinks\Core\Exceptions\InvalidUrlException;
use RichardRoman\ShortLinks\Core\Services\ShortLinkService;
use RichardRoman\ShortLinks\Core\Validators\HttpUrlValidator;
use RichardRoman\ShortLinks\Core\ValueObjects\ShortLink;
use RichardRoman\ShortLinks\Tests\Support\FixedCodeGenerator;
use RichardRoman\ShortLinks\Tests\Support\InMemoryShortLinkRepository;

final class ShortLinkServiceTest extends TestCase
{
    public function test_creates_short_link_with_valid_url(): void
    {
        $repository = new InMemoryShortLinkRepository();
        $service = new ShortLinkService(
            repository: $repository,
            urlValidator: new HttpUrlValidator(),
            codeGenerator: new FixedCodeGenerator('k7mnp2wx'),
        );

        $shortLink = $service->create('https://example.com/video', titulo: 'Demo');

        $this->assertSame('k7mnp2wx', $shortLink->codigo);
        $this->assertSame('https://example.com/video', $shortLink->urlDestino);
        $this->assertSame('Demo', $shortLink->titulo);
        $this->assertTrue($shortLink->activo);
    }

    public function test_reuses_existing_entity_link(): void
    {
        $repository = new InMemoryShortLinkRepository();
        $repository->seed(new ShortLink(
            id: 'existing-id',
            codigo: 'existing1',
            urlDestino: 'https://example.com/old',
            entidadTipo: 'entregable',
            entidadId: 'ent-1',
        ));

        $service = new ShortLinkService(
            repository: $repository,
            urlValidator: new HttpUrlValidator(),
            codeGenerator: new FixedCodeGenerator('unused99'),
        );

        $shortLink = $service->create(
            urlDestino: 'https://example.com/new',
            entidadTipo: 'entregable',
            entidadId: 'ent-1',
        );

        $this->assertSame('existing1', $shortLink->codigo);
        $this->assertSame(0, $repository->getCreateAttempts());
    }

    public function test_rejects_invalid_url(): void
    {
        $service = new ShortLinkService(
            repository: new InMemoryShortLinkRepository(),
            urlValidator: new HttpUrlValidator(),
            codeGenerator: new FixedCodeGenerator('abcdefgh'),
        );

        $this->expectException(InvalidUrlException::class);

        $service->create('javascript:alert(1)');
    }

    public function test_retries_on_duplicate_code(): void
    {
        $repository = new InMemoryShortLinkRepository();
        $repository->rejectCodigoOnce('dup12345');

        $service = new ShortLinkService(
            repository: $repository,
            urlValidator: new HttpUrlValidator(),
            codeGenerator: new FixedCodeGenerator('dup12345', 'unique12'),
        );

        $shortLink = $service->create('https://example.com/retry');

        $this->assertSame('unique12', $shortLink->codigo);
        $this->assertSame(2, $repository->getCreateAttempts());
    }

    public function test_fails_after_max_duplicate_attempts(): void
    {
        $repository = new InMemoryShortLinkRepository();
        $repository->rejectNextCreates(5);

        $service = new ShortLinkService(
            repository: $repository,
            urlValidator: new HttpUrlValidator(),
            codeGenerator: new FixedCodeGenerator('a', 'b', 'c', 'd', 'e'),
        );

        $this->expectException(CodeGenerationFailedException::class);

        $service->create('https://example.com/fail');
    }
}
