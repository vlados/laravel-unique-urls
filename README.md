# Laravel Unique Urls

[![Latest Version on Packagist](https://img.shields.io/packagist/v/vlados/laravel-unique-urls.svg?style=flat-square)](https://packagist.org/packages/vlados/laravel-unique-urls)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/vlados/laravel-unique-urls/run-tests?label=tests)](https://github.com/vlados/laravel-unique-urls/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/vlados/laravel-unique-urls/Check%20&%20fix%20styling?label=code%20style)](https://github.com/vlados/laravel-unique-urls/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/vlados/laravel-unique-urls.svg?style=flat-square)](https://packagist.org/packages/vlados/laravel-unique-urls)

Generate unique urls for blogs, ecommerce and platforms without prefix.
Laravel 

- [Installation](#installation)
- [Usage](#usage)
    - [Configuration](#configuration)
    - [Routes]()
    - [Prepare your model](#prepare-your-model)
    - [Disable auto creating urls](#batch-import)
    - [Livewire](#livewire)
- [Contributing](#contributing)

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


## Usage

### Configuration
You can publish the config file with:
```bash
php artisan vendor:publish --tag="laravel-unique-urls-config"
```
There will create `unique-urls.php` with:
```php
return [
    // Language versions of the urls
    // 
    'languages' => ['bg','en'],
    'redirect_http_code' => 301,
];
```

### Prepare your model
In your Model add these methods:
```php
class MyModel extends Model
{
    use Vlados\LaravelUniqueUrls\HasUniqueUrlTrait;

    public function urlStrategy(): string
    {
        return Str::slug($this->getAttribute('name'));
    }
    
    public function urlHandler(): array
    {
        return [
            // The controller used to handle the request
            'controller' => CategoryController::class,
            // The method
            'method' => 'view',
            // additional arguments sent to this method
            'arguments' => [],
        ];
    }
```

The method for handling the request:
```php
public function view(Request $request, $arguments = [])
{
    dd($arguments);
}
```

And last, add this line at the end of your `routes/web.php`
```php
Route::get('{urlObj}', [\Vlados\LaravelUniqueUrls\LaravelUniqueUrls::class, 'handleRequest'])->where('urlObj', '.*');
```
### Batch import
If for example you have category tree and you need to import all the data before creating the urls, you can disable the automatic generation of the url on model creation 
To disable automatically generating the urls on create or update overwrite the method `isAutoGenerateUrls` in the model:
```php
public function isAutoGenerateUrls(): bool
{
    return false;
}
```
and call `generateUrl()` later like this:
```php
YourModel::all()->each(function (YourModel $model) {
    $model->generateUrl();
});
```
### Livewire
To use [Livewire full-page component](https://laravel-livewire.com/docs/2.x/rendering-components#page-components) to handle the request, first set in `urlHandler()` function in your model:
```php
public function urlHandler(): array
{
    return [
        // The Livewire controller
        'controller' => CategoryController::class,
        // The method should be empty
        'method' => '',
        // additional arguments sent to the mount() function
        'arguments' => [],
    ];
}
```
Example livewire component:
```php
class LivewireComponentExample extends Component
{
    private Url $urlModel;
    private array $url_arguments;

    public function mount(Url $urlObj, $arguments = [])
    {
        $this->urlModel = $urlObj;
        $this->url_arguments = $arguments;
    }

    public function render()
    {
        return view('livewire.view-category');
    }
}

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
