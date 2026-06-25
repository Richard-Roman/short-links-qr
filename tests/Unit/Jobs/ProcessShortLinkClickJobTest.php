<?php

namespace RichardRoman\ShortLinks\Tests\Unit\Jobs;

use Mockery;
use RichardRoman\ShortLinks\Contracts\ShortLinkRepositoryInterface;
use RichardRoman\ShortLinks\Core\Data\ClickData;
use RichardRoman\ShortLinks\Core\ValueObjects\ShortLink;
use RichardRoman\ShortLinks\Laravel\Jobs\ProcessShortLinkClickJob;
use RichardRoman\ShortLinks\Tests\TestCase;

final class ProcessShortLinkClickJobTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_calls_repository_to_record_click(): void
    {
        $shortLink = new ShortLink(
            id: 'abc-123',
            codigo: 'qwerty',
            urlDestino: 'https://example.com'
        );

        $repositoryMock = Mockery::mock(ShortLinkRepositoryInterface::class);

        $repositoryMock->shouldReceive('incrementClicksAndRecord')
            ->once()
            ->withArgs(function (ShortLink $link, ClickData $data) use ($shortLink) {
                return $link->id === $shortLink->id
                    && $data->ipHash === 'hash123'
                    && $data->referrer === 'twitter'
                    && $data->userAgent === 'chrome';
            });

        $job = new ProcessShortLinkClickJob(
            shortLink: $shortLink,
            ipHash: 'hash123',
            referrer: 'twitter',
            userAgent: 'chrome'
        );

        $job->handle($repositoryMock);

        $this->assertTrue(true);
    }
}
