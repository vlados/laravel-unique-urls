<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Available Languages
    |--------------------------------------------------------------------------
    |
    | Define the available languages for URL generation. The key is the locale
    | (e.g., 'bg_BG') and the value is the language code (e.g., 'bg').
    |
    */
    'languages' => [
        'bg_BG' => 'bg',
        'en_US' => 'en',
        'de_DE' => 'de',
    ],

    /*
    |--------------------------------------------------------------------------
    | Redirect HTTP Code
    |--------------------------------------------------------------------------
    |
    | HTTP status code used when redirecting from old URLs to new URLs.
    | Default: 301 (Permanent Redirect)
    |
    */
    'redirect_http_code' => 301,

    /*
    |--------------------------------------------------------------------------
    | Auto-Trim Slashes
    |--------------------------------------------------------------------------
    |
    | Automatically trim leading and trailing slashes from slugs. This prevents
    | 404 errors when urlStrategy() methods return slugs with slashes.
    | Default: true (recommended)
    |
    */
    'auto_trim_slashes' => true,

    /*
    |--------------------------------------------------------------------------
    | Validate Slugs
    |--------------------------------------------------------------------------
    |
    | Enable strict validation of slug format. When enabled, slugs must only
    | contain lowercase letters, numbers, and hyphens. Invalid characters
    | will throw an InvalidSlugException.
    | Default: false
    |
    */
    'validate_slugs' => false,

    /*
    |--------------------------------------------------------------------------
    | Reserved Slugs
    |--------------------------------------------------------------------------
    |
    | List of reserved slugs that cannot be used for URL generation.
    | Common examples: admin, api, login, logout, register, etc.
    | Set to empty array to disable.
    |
    */
    'reserved_slugs' => [
        'admin',
        'api',
        'login',
        'logout',
        'register',
        'password',
        'dashboard',
    ],

    /*
    |--------------------------------------------------------------------------
    | Batch Size
    |--------------------------------------------------------------------------
    |
    | Number of records to process in a single batch when generating URLs
    | via the urls:generate command. Larger values use more memory but may
    | be faster. Smaller values are safer for large datasets.
    | Default: 500
    |
    */
    'batch_size' => 500,

    /*
    |--------------------------------------------------------------------------
    | Auto Generate on Create
    |--------------------------------------------------------------------------
    |
    | Automatically generate URLs when a model is created. If false, you must
    | manually call $model->generateUrl() or use the urls:generate command.
    | Models can override this with isAutoGenerateUrls() method.
    | Default: true
    |
    */
    'auto_generate_on_create' => true,

    /*
    |--------------------------------------------------------------------------
    | Create Redirects
    |--------------------------------------------------------------------------
    |
    | Automatically create redirect entries when URLs are changed. This ensures
    | old URLs continue to work and redirect to the new URL with the configured
    | HTTP status code.
    | Default: true (recommended for SEO)
    |
    */
    'create_redirects' => true,
];
