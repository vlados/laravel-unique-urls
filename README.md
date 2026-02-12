# Unique Urls for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/vlados/laravel-unique-urls.svg?style=flat-square)](https://packagist.org/packages/vlados/laravel-unique-urls)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/vlados/laravel-unique-urls/run-tests?label=tests)](https://github.com/vlados/laravel-unique-urls/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/vlados/laravel-unique-urls/Check%20&%20fix%20styling?label=code%20style)](https://github.com/vlados/laravel-unique-urls/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/vlados/laravel-unique-urls.svg?style=flat-square)](https://packagist.org/packages/vlados/laravel-unique-urls)
[![PHP Insights](https://github.com/vlados/laravel-unique-urls/actions/workflows/insights.yaml/badge.svg)](https://github.com/vlados/laravel-unique-urls/actions/workflows/insights.yaml)
[![PHPStan](https://github.com/vlados/laravel-unique-urls/actions/workflows/phpstan.yml/badge.svg)](https://github.com/vlados/laravel-unique-urls/actions/workflows/phpstan.yml)

Generate unique, prefix-free URLs for any Eloquent model — blogs, e-commerce, multi-language platforms.

**PHP 8.2+ | Laravel 11, 12**

## Features

- Auto-trim slashes to prevent 404 errors
- Multi-language URLs (distinct slugs per locale, not just prefixes)
- Automatic redirects when URLs change (301 by default)
- Hierarchical URLs via parent relationships (e.g. `category/product`)
- Livewire full-page component support (FQCN and SFC names)
- Batch generation with progress tracking for large datasets
- Slug validation and reserved-slug protection
- `urls:doctor` command for configuration health checks

## Installation

```bash
composer require vlados/laravel-unique-urls
php artisan vendor:publish --tag="laravel-unique-urls-migrations"
php artisan migrate
```

## Quick Start

Add the trait to your model and define how URLs are built:

```php
use Vlados\LaravelUniqueUrls\HasUniqueUrls;

class Product extends Model
{
    use HasUniqueUrls;

    public function urlStrategy($language, $locale): string
    {
        return Str::slug($this->name, '-', $locale);
    }

    public function urlHandler(): array
    {
        return [
            'controller' => ProductController::class,
            'method'     => 'view',
            'arguments'  => [],
        ];
    }
}
```

Register the catch-all route at the **end** of `routes/web.php`:

```php
Route::get('{urlObj}', [\Vlados\LaravelUniqueUrls\LaravelUniqueUrlsController::class, 'handleRequest'])
    ->where('urlObj', '.*');
```

Access URLs on any model instance:

```php
$product->relative_url;  // "my-product"
$product->absolute_url;  // "https://example.com/my-product"
$product->getSlug('en'); // slug for a specific language
```

## Documentation

- [Configuration](docs/configuration.md) — config options and defaults
- [Usage](docs/usage.md) — model setup, routes, multi-language, Livewire
- [Bulk Operations](docs/bulk-operations.md) — batch generation, global toggles, performance
- [Artisan Commands](docs/artisan-commands.md) — `urls:generate` and `urls:doctor`
- [Testing](docs/testing.md) — assertion helpers and example tests
- [Troubleshooting](docs/troubleshooting.md) — common issues and fixes

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for release history.

## Contributing

This project follows [Conventional Commits](https://www.conventionalcommits.org/).

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Vladislav Stoitsov](https://github.com/vlados)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). See [LICENSE.md](LICENSE.md) for details.
