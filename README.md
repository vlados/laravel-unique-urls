# Laravel Unique Urls

[![Latest Version on Packagist](https://img.shields.io/packagist/v/vlados/laravel-unique-urls.svg?style=flat-square)](https://packagist.org/packages/vlados/laravel-unique-urls)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/vlados/laravel-unique-urls/run-tests?label=tests)](https://github.com/vlados/laravel-unique-urls/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/vlados/laravel-unique-urls/Check%20&%20fix%20styling?label=code%20style)](https://github.com/vlados/laravel-unique-urls/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/vlados/laravel-unique-urls.svg?style=flat-square)](https://packagist.org/packages/vlados/laravel-unique-urls)

A package for using and generating unique urls for each Eloquent model in Laravel. This package is inspired by [spatie/laravel-sluggable](https://github.com/spatie/laravel-sluggable) but making the urls unique.

### Goals:
- When create or update a model to generate a unique url based on urlStrategy() function inside each model
- If the url exists to create a new url with suffix _1, _2, etc.
- If we update the model to create a redirect from the old to the new url


## Installation

You can install the package via composer:

```bash
composer require vlados/laravel-unique-urls
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="laravel-unique-urls-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-unique-urls-config"
```

## Usage

```php
$laravelUniqueUrls = new Vlados\LaravelUniqueUrls();
echo $laravelUniqueUrls->echoPhrase('Hello, Vlados!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Vladislav Stoitsov](https://github.com/vlados)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
