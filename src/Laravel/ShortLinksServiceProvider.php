<?php

namespace RichardRoman\ShortLinks\Laravel;

use Illuminate\Support\ServiceProvider;
use RichardRoman\ShortLinks\Contracts\CodeGeneratorInterface;
use RichardRoman\ShortLinks\Contracts\EntityResolverRegistryInterface;
use RichardRoman\ShortLinks\Contracts\QrGeneratorInterface;
use RichardRoman\ShortLinks\Contracts\RedirectCacheInterface;
use RichardRoman\ShortLinks\Contracts\ShortLinkRepositoryInterface;
use RichardRoman\ShortLinks\Contracts\UrlValidatorInterface;
use RichardRoman\ShortLinks\Core\Generators\RandomCodeGenerator;
use RichardRoman\ShortLinks\Core\Resolvers\EntityResolverRegistry;
use RichardRoman\ShortLinks\Core\Services\QrService;
use RichardRoman\ShortLinks\Core\Services\RedirectService;
use RichardRoman\ShortLinks\Core\Services\ShortLinkService;
use RichardRoman\ShortLinks\Core\Validators\HttpUrlValidator;
use RichardRoman\ShortLinks\Laravel\Actions\RecordClickAction;
use RichardRoman\ShortLinks\Laravel\Cache\IlluminateRedirectCache;
use RichardRoman\ShortLinks\Laravel\Mappers\ShortLinkMapper;
use RichardRoman\ShortLinks\Laravel\Qr\EndroidQrGenerator;
use RichardRoman\ShortLinks\Laravel\Repositories\EloquentShortLinkRepository;

class ShortLinksServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/short-links.php', 'short-links');

        $this->app->singleton(ShortLinkMapper::class);

        $this->app->singleton(UrlValidatorInterface::class, HttpUrlValidator::class);
        $this->app->singleton(CodeGeneratorInterface::class, function (): RandomCodeGenerator {
            return new RandomCodeGenerator(
                charset: (string) config('short-links.generator.charset', 'abcdefghjkmnpqrstuvwxyz23456789'),
                length: (int) config('short-links.generator.length', 8),
            );
        });
        $this->app->singleton(QrGeneratorInterface::class, EndroidQrGenerator::class);
        $this->app->singleton(RedirectCacheInterface::class, IlluminateRedirectCache::class);

        $this->app->singleton(ShortLinkRepositoryInterface::class, EloquentShortLinkRepository::class);

        $this->app->singleton(EntityResolverRegistryInterface::class, function ($app): EntityResolverRegistry {
            return new EntityResolverRegistry($app->tagged('short-links.entity-resolvers'));
        });

        $this->app->singleton(ShortLinkService::class, function ($app): ShortLinkService {
            return new ShortLinkService(
                repository: $app->make(ShortLinkRepositoryInterface::class),
                urlValidator: $app->make(UrlValidatorInterface::class),
                codeGenerator: $app->make(CodeGeneratorInterface::class),
                routePattern: (string) config('short-links.route_pattern', '[a-hjkmnp-z2-9]{8}'),
            );
        });
        $this->app->singleton(RedirectService::class);
        $this->app->singleton(QrService::class);
        $this->app->singleton(RecordClickAction::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/short-links.php' => config_path('short-links.php'),
            ], 'short-links-config');
        }
    }
}
