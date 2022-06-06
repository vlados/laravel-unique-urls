# Laravel Unique Urls

[![Latest Version on Packagist](https://img.shields.io/packagist/v/vlados/laravel-unique-urls.svg?style=flat-square)](https://packagist.org/packages/vlados/laravel-unique-urls)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/vlados/laravel-unique-urls/run-tests?label=tests)](https://github.com/vlados/laravel-unique-urls/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/vlados/laravel-unique-urls/Check%20&%20fix%20styling?label=code%20style)](https://github.com/vlados/laravel-unique-urls/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/vlados/laravel-unique-urls.svg?style=flat-square)](https://packagist.org/packages/vlados/laravel-unique-urls)

A package for using and generating unique urls for each Eloquent model in Laravel. This package is inspired by [spatie/laravel-sluggable](https://github.com/spatie/laravel-sluggable) but making the urls unique.

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/laravel-unique-urls.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/laravel-unique-urls)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

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
