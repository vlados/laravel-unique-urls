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
| `redirect_http_code` | `int` | `301` | HTTP status code for old-URL redirects |
| `auto_trim_slashes` | `bool` | `true` | Strip leading/trailing slashes from slugs |
| `validate_slugs` | `bool` | `false` | Enforce lowercase-alphanumeric-hyphen format |
| `reserved_slugs` | `array` | `['admin', 'api', ...]` | Slugs that cannot be used (throws `InvalidSlugException`) |
| `batch_size` | `int` | `500` | Records per chunk during batch URL generation |
| `auto_generate_on_create` | `bool` | `true` | Auto-generate URLs on model create/update |
| `create_redirects` | `bool` | `true` | Create redirect entries when URLs change |
