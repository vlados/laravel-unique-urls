# Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag="laravel-unique-urls-config"
```

This creates `config/unique-urls.php`:

```php
return [
    'languages' => [
        'bg_BG' => 'bg',
        'en_US' => 'en',
        'de_DE' => 'de',
    ],

    'model_paths' => null,

    'redirect_http_code' => 301,

    'auto_trim_slashes' => true,

    'validate_slugs' => false,

    'reserved_slugs' => [
        'admin',
        'api',
        'login',
        'logout',
        'register',
        'password',
        'dashboard',
    ],

    'batch_size' => 500,

    'auto_generate_on_create' => true,

    'create_redirects' => true,
];
```

## Options Reference

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `languages` | `array` | `['bg_BG'=>'bg', ...]` | Locale-to-language map for URL generation |
| `model_paths` | `?array` | `null` | Directories scanned for models by `urls:generate` and `urls:doctor` (`null` = auto) |
| `redirect_http_code` | `int` | `301` | HTTP status code for old-URL redirects |
| `auto_trim_slashes` | `bool` | `true` | Strip leading/trailing slashes from slugs |
| `validate_slugs` | `bool` | `false` | Enforce lowercase-alphanumeric-hyphen format |
| `reserved_slugs` | `array` | `['admin', 'api', ...]` | Slugs that cannot be used (throws `InvalidSlugException`) |
| `batch_size` | `int` | `500` | Records per chunk during batch URL generation |
| `auto_generate_on_create` | `bool` | `true` | Auto-generate URLs on model create/update |
| `create_redirects` | `bool` | `true` | Create redirect entries when URLs change |

## Model Paths

`urls:generate` and `urls:doctor` have to find your models before they can do
anything with them. With `model_paths` left at `null` they scan:

- `app_path()` — the classic location, `App\Models\…`
- every module under the modules directory — `config('modules.paths.modules')`
  when [nwidart/laravel-modules](https://github.com/nWidart/laravel-modules) is
  installed, otherwise `base_path('Modules')`

Both module layouts are understood: `Modules/{Module}/app` (v10+, the PSR-4 root
of `Modules\{Module}\`) and the older `Modules/{Module}/Models` and
`Modules/{Module}/Entities`.

Set an array to take over the list completely:

```php
'model_paths' => [
    app_path(),

    // Namespace guessed from the composer.json PSR-4 map
    base_path('src/Domain'),

    // …or stated explicitly: namespace root => directory
    'App\Domain' => base_path('src/Domain'),

    // Full control, for a scan directory below the namespace root
    [
        'path' => base_path('src/Domain/Catalog/Models'),
        'base_path' => base_path('src/Domain'),
        'namespace' => 'App\Domain',
    ],
],
```

Directories that do not exist are skipped, so listing an optional path is safe.

> Nothing is added implicitly to an explicit list — include `app_path()` yourself
> if your `App\Models` classes still need generating.
