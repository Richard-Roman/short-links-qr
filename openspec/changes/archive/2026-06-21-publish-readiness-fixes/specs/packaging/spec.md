# Packaging Specification

## Purpose

Configure the package distribution settings (autoloading maps) and configuration-driven dependency bindings so the `short-links-qr` package is production-ready and customizable by consuming applications.

## Requirements

### Requirement: Factory Autoload PSR-4 Mapping in Production

The database factories of this package MUST be autoloaded under the standard `autoload` block (rather than `autoload-dev`) of `composer.json`. This ensures consuming Laravel applications can resolve package factory classes dynamically in production, staging, and seeding environments without requiring dev-dependencies.

#### Scenario: Resolving ShortLink factory in a consuming application
- GIVEN the `short-links-qr` package is installed in a Laravel application with the `--no-dev` Composer flag
- WHEN the application attempts to resolve or instantiate `RichardRoman\ShortLinks\Database\Factories\ShortLinkFactory`
- THEN the factory class is successfully loaded and instantiated without throwing a Class Not Found exception.

### Requirement: Configurable QR Generator Implementation

The Laravel service provider (`ShortLinksServiceProvider`) MUST dynamically resolve the concrete implementation bound to `QrGeneratorInterface` from the package configuration (`config/short-links.php`), defaulting to `EndroidQrGenerator`. This allows consuming applications to override the default generator with their own custom adapter implementation.

#### Scenario: Resolving default QR generator implementation
- GIVEN the application has the default configuration mapping for `short-links.qr_generator` set to `RichardRoman\ShortLinks\Laravel\Qr\EndroidQrGenerator`
- WHEN the application requests an instance of `RichardRoman\ShortLinks\Contracts\QrGeneratorInterface` from the service container
- THEN the service container MUST return an instance of `RichardRoman\ShortLinks\Laravel\Qr\EndroidQrGenerator`.

#### Scenario: Resolving custom QR generator implementation
- GIVEN a custom class `App\ShortLinks\CustomQrGenerator` that implements `RichardRoman\ShortLinks\Contracts\QrGeneratorInterface`
- AND the application config `short-links.qr_generator` is configured to use `App\ShortLinks\CustomQrGenerator`
- WHEN the application requests an instance of `RichardRoman\ShortLinks\Contracts\QrGeneratorInterface` from the service container
- THEN the service container MUST return an instance of `App\ShortLinks\CustomQrGenerator`.
