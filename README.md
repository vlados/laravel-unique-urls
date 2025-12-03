# Unique Urls for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/vlados/laravel-unique-urls.svg?style=flat-square)](https://packagist.org/packages/vlados/laravel-unique-urls)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/vlados/laravel-unique-urls/run-tests?label=tests)](https://github.com/vlados/laravel-unique-urls/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/vlados/laravel-unique-urls/Check%20&%20fix%20styling?label=code%20style)](https://github.com/vlados/laravel-unique-urls/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/vlados/laravel-unique-urls.svg?style=flat-square)](https://packagist.org/packages/vlados/laravel-unique-urls)
[![PHP Insights](https://github.com/vlados/laravel-unique-urls/actions/workflows/insights.yaml/badge.svg)](https://github.com/vlados/laravel-unique-urls/actions/workflows/insights.yaml)
[![PHPStan](https://github.com/vlados/laravel-unique-urls/actions/workflows/phpstan.yml/badge.svg)](https://github.com/vlados/laravel-unique-urls/actions/workflows/phpstan.yml)

Generate unique urls for blogs, ecommerce and platforms without prefix.

**Supports Laravel 9-12 | PHP 8.1-8.4**

## ✨ Features

- ✅ **Auto-trim slashes** - Automatically removes leading/trailing slashes to prevent 404 errors
- ✅ **Custom exceptions** - Detailed error messages with model context for easier debugging
- ✅ **Optional validation** - Enforce slug format rules and reserved slug protection
- ✅ **Progress tracking** - Visual progress bars for large URL generation operations
- ✅ **Multi-language support** - Different URLs for different languages, not just prefixes
- ✅ **Automatic redirects** - Old URLs automatically redirect to new ones when updated
- ✅ **Hierarchical URLs** - Support for URLs with parent relationships (e.g., category/product)
- ✅ **Livewire integration** - Full support for Livewire full-page components
- ✅ **Batch operations** - Efficient URL generation for thousands of models

- [Installation](#installation)
- [Usage](#usage)
    - [Configuration](#configuration)
    - [Routes](#routes)
    - [Prepare your model](#prepare-your-model)
    - [Disable auto creating urls](#batch-import)
    - [Livewire](#livewire)
- [Commands](#commands)
- [Common Issues](#common-issues)
- [Performance Tips](#performance-tips)
- [Contributing](#contributing)

### Goals:
- When create or update a model to generate a unique url based on urlStrategy() function inside each model
- Possibility to have different urls for the different languages (not only a prefix in the beginning)
- If the url exists to create a new url with suffix _1, _2, etc.
- If we update the model to create a redirect from the old to the new url
- If there is a multiple redirects to redirect only to the last one
- Possibility to have an url depending on relations (category-name/product-name)


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
This will create `config/unique-urls.php` with all available options:

```php
return [
    /*
    |--------------------------------------------------------------------------
    | Available Languages
    |--------------------------------------------------------------------------
    |
    | Define the available languages for URL generation.
    | Format: 'locale' => 'language_code'
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
    | Automatically trim leading and trailing slashes from slugs.
    | This prevents 404 errors when urlStrategy() returns slugs with slashes.
    | Recommended: true
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
    | Common examples: admin, api, login, etc.
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
    | Number of records to process in a single batch when generating URLs.
    | Larger values use more memory but may be faster.
    | Default: 500
    |
    */
    'batch_size' => 500,

    /*
    |--------------------------------------------------------------------------
    | Auto Generate on Create
    |--------------------------------------------------------------------------
    |
    | Automatically generate URLs when a model is created.
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
    | Automatically create redirect entries when URLs are changed.
    | This ensures old URLs continue to work (recommended for SEO).
    | Default: true
    |
    */
    'create_redirects' => true,
];
```

### Prepare your model
In your Model add these methods:

```php
class MyModel extends Model
{
    use Vlados\LaravelUniqueUrls\HasUniqueUrls;

    public function urlStrategy($language,$locale): string
    {
        return Str::slug($this->getAttribute('name'),"-",$locale);
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
### Routes
And last, add this line at the end of your `routes/web.php`

```php
Route::get('{urlObj}', [\Vlados\LaravelUniqueUrls\LaravelUniqueUrlsController::class, 'handleRequest'])->where('urlObj', '.*');
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

or if you want to disable it on the go, use
```php
$model = new TestModel();
$model->disableGeneratingUrlsOnCreate();
$model->name = "Test";
$model->save();
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

## API

| **Methods**                     	 | Description                                                 	| Parameters         	|
|-----------------------------------|-------------------------------------------------------------	|--------------------	|
| generateUrl()                   	 | Generate manually the URL for a single model                |                    	|
| **generateUrlsInBatch()** (static) | Generate URLs for multiple models with memory optimization  | $models, $chunkSize = 500, $callback = null |
| getSlug()                       	 | Get the URL for a specific language in relative or absolute format | ?string $language = '', bool $relative = true |
| urlStrategy                     	 | The strategy for creating the URL for the model             	| $language, $locale 	|
| isAutoGenerateUrls()            	 | Check if URLs should be generated automatically for the model |                    	|
| disableGeneratingUrlsOnCreate() 	 | Disable generating urls on creation for this instance       |                    	|
| **Properties**                  	 |                                                             	|                    	|
| relative_url                    	 | The url path, relative to the site url                      	|                    	|
| absolute_url                    	 | The absolute url, including the domain                      	|                    	|
| **Relations**                   	 |                                                             	|                    	|
| urls()                          	 | All the active urls, related to the current model           	|                    	|

### Getting Model URLs

```php
// Get relative URL for current locale
$model->relative_url; // e.g., "my-product-name"

// Get absolute URL for current locale
$model->absolute_url; // e.g., "https://example.com/my-product-name"

// Get URL for specific language (relative)
$model->getSlug('en', true); // e.g., "my-product-name"

// Get URL for specific language (absolute)
$model->getSlug('en', false); // e.g., "https://example.com/my-product-name"

// Get URL for current locale (defaults to app locale)
$model->getSlug(); // e.g., "my-product-name"
```

### Batch URL Generation

For processing large numbers of models efficiently, use the static `generateUrlsInBatch()` method:

```php
use App\Models\Product;

// Basic usage: generate URLs for all products without URLs
$products = Product::whereDoesntHave('urls')->get();
$stats = Product::generateUrlsInBatch($products);

// Returns: ['generated' => 150, 'skipped' => 0, 'failed' => 0]

// With custom chunk size for memory optimization
$products = Product::all();
$stats = Product::generateUrlsInBatch($products, chunkSize: 100);

// With progress callback
use Illuminate\Support\Facades\Log;

$products = Product::all();
$stats = Product::generateUrlsInBatch(
    $products,
    chunkSize: 500,
    callback: function ($model, $processed, $total, $stats) {
        if ($processed % 100 === 0) {
            Log::info("URL generation progress: {$processed}/{$total}", $stats);
        }
    }
);

// With progress bar in command
$products = Product::all();
$bar = $this->output->createProgressBar($products->count());

$stats = Product::generateUrlsInBatch(
    $products,
    callback: function () use ($bar) {
        $bar->advance();
    }
);

$bar->finish();
$this->info("Generated: {$stats['generated']}, Skipped: {$stats['skipped']}, Failed: {$stats['failed']}");
```

**Benefits:**
- Automatic memory management with garbage collection
- Progress tracking via callback
- Error handling - continues processing even if some models fail
- Returns statistics about the operation
- Processes models in configurable chunk sizes

## Commands

### urls:generate
This command generates unique URLs for the specified model or all models that implement the `HasUniqueUrls` trait.

#### Usage:
```bash
php artisan urls:generate [options]
```

#### Options:
- `--model=ModelName` - Generate URLs only for a specific model (accepts full class name or short name)
- `--fresh` - Truncate URLs table and regenerate all URLs from scratch
- `--only-missing` - Only generate URLs for models that don't have URLs yet
- `--chunk-size=500` - Number of records to process per chunk (default: 500)
- `--force` - Force generation even if `isAutoGenerateUrls()` returns false

#### Examples:
```bash
# Generate URLs for all models
php artisan urls:generate

# Generate URLs only for Product model
php artisan urls:generate --model="App\Models\Product"

# Generate only missing URLs for Product model
php artisan urls:generate --model=Product --only-missing

# Fresh generation with custom chunk size
php artisan urls:generate --fresh --chunk-size=1000

# Force generation for models with isAutoGenerateUrls() = false
php artisan urls:generate --model=Product --force
```

#### Enhanced Output:
The command now provides detailed feedback:

```bash
$ php artisan urls:generate --model=Product

Generating URLs for App\Models\Product...

├─ Found: 14,031 models
├─ With URLs: 14,030
├─ Without URLs: 1

 1/1 [============================] 100% | Generating...

├─ Generated: 1 URL
└─ ✓ Completed

═══════════════════════════════════════
           Summary
═══════════════════════════════════════
  Generated: 1 URLs
  Duration: 0.5s
═══════════════════════════════════════

All done ✓
```

#### Warning for isAutoGenerateUrls():
If a model has `isAutoGenerateUrls()` returning false, you'll see:

```bash
⚠ App\Models\Product has isAutoGenerateUrls() = false
  URLs will not be generated automatically.
  Use --force flag to generate anyway, or enable in model.
```

### urls:doctor
This command checks if all models have implemented the HasUniqueUrls trait and required functions correctly.

#### Usage:
```bash
php artisan urls:doctor [--model=ModelName]
```

#### Checks:
- Verifies all required methods are implemented with correct parameters
- Validates that urlHandler() returns correct controller and method
- Checks if urlStrategy() generates unique URLs for all languages
- Detects common implementation issues

## Common Issues

### URLs have leading/trailing slashes causing 404 errors

**Automatic Fix (v1.1.0+):** Leading and trailing slashes are now automatically trimmed.

**Before:**
```php
// urlStrategy() returns: '/product-name/' or null . '/' . 'product' = '/product'
// Result: 404 error ❌
```

**After (v1.1.0+):**
```php
// urlStrategy() returns: '/product-name/' or '/product'
// Automatically trimmed to: 'product-name' or 'product' ✅
// Warning logged for debugging
```

**Solution:**
Update to v1.1.0+ or fix your `urlStrategy()` method:
```php
public function urlStrategy($language, $locale): string
{
    // ✅ Good: Returns clean slug
    return Str::slug($this->name);

    // ❌ Avoid: Returns slug with slashes
    // return '/' . Str::slug($this->name) . '/';

    // ✅ OK: Auto-trimmed (but will log warning)
    // return $this->parent?->getSlug() . '/' . Str::slug($this->name);
}
```

### Empty slug exceptions

**Error message (v1.1.0+):**
```
Cannot generate URL: empty slug for App\Models\Product (ID: 123).
Check the urlStrategy() method returns a non-empty string.
```

**Common causes:**
1. `urlStrategy()` returns empty string or null
2. Slug becomes empty after trimming (e.g., only slashes: `'/'`)
3. Model attribute used in slug is empty

**Solution:**
```php
public function urlStrategy($language, $locale): string
{
    // ✅ Ensure name is not empty
    if (empty($this->name)) {
        throw new \Exception('Cannot generate URL: product name is empty');
    }

    return Str::slug($this->name);
}
```

### TypeError when using url() helper with model URLs

**Error:**
```
TypeError: Argument #2 ($url) must be of type ?string, Illuminate\Routing\UrlGenerator given
```

**Cause:** Passing `null` to `url()` helper (fixed in v1.1.0+)

**Solution (v1.1.0+):**
```php
// ✅ Safe: getSlug() returns empty string instead of null
$url = url($model->relative_url); // Works even if no URL exists

// ✅ Safe: Use null coalescing for older versions
$url = url($model->relative_url ?? '');
```

### URLs not generating automatically

**Check these:**
1. Does your model use the `HasUniqueUrls` trait?
2. Did you implement `urlStrategy()` and `urlHandler()` methods?
3. Is `isAutoGenerateUrls()` returning `true` (or not defined)?
4. Is the catch-all route registered in `routes/web.php`?

**Force generation:**
```bash
# Generate URLs even if isAutoGenerateUrls() = false
php artisan urls:generate --model=Product --force
```

### Reserved slug errors

**Error (when validation enabled):**
```
Invalid slug 'admin' for App\Models\Page (ID: 1): slug is reserved.
Reserved slugs are defined in config/unique-urls.php. Choose a different slug.
```

**Solution:**
Either change the slug or remove it from reserved list in `config/unique-urls.php`:
```php
'reserved_slugs' => [
    // 'admin', // Remove if you need to use this slug
    'api',
    'login',
],
```

## Performance Tips

### For large datasets (10,000+ models):

**1. Use chunking:**
```bash
# Process in smaller chunks to reduce memory usage
php artisan urls:generate --model=Product --chunk-size=100
```

**2. Generate only missing URLs:**
```bash
# Skip existing URLs to save time
php artisan urls:generate --only-missing
```

**3. Disable during imports:**
```php
// Disable auto-generation during bulk imports
public function isAutoGenerateUrls(): bool
{
    return false;
}

// Generate URLs after import completes
Product::all()->each(fn($p) => $p->generateUrl());
```

**4. Use database indexing:**
```php
// Add indexes to urls table for better performance
Schema::table('urls', function (Blueprint $table) {
    $table->index('slug');
    $table->index(['related_type', 'related_id']);
    $table->index('language');
});
```

**5. Cache URL queries:**
```php
// Cache frequently accessed URLs
$url = Cache::remember(
    "product_url_{$product->id}",
    now()->addHour(),
    fn() => $product->relative_url
);
```

### Memory optimization:

**Process large datasets in chunks:**
```php
// Instead of:
Product::all()->each(fn($p) => $p->generateUrl()); // ❌ Loads all into memory

// Use chunking:
Product::chunk(500, function ($products) { // ✅ Processes 500 at a time
    foreach ($products as $product) {
        $product->generateUrl();
    }
});
```

## Testing

### Running Package Tests

```bash
composer test
```

### Testing Helpers for Your Application

The package provides a testing trait with helpful assertions for testing URL generation in your application.

#### Setup

In your test class, use the `AssertsUniqueUrls` trait:

```php
use Tests\TestCase;
use Vlados\LaravelUniqueUrls\Testing\AssertsUniqueUrls;

class ProductTest extends TestCase
{
    use AssertsUniqueUrls;

    public function test_product_has_url()
    {
        $product = Product::factory()->create(['name' => 'Test Product']);

        $this->assertHasUrl($product);
    }
}
```

#### Available Assertions

```php
// Assert model has at least one URL
$this->assertHasUrl($product);

// Assert model has no URLs
$this->assertHasNoUrl($product);

// Assert URL doesn't start with slash
$this->assertNoLeadingSlash($product);
$this->assertNoLeadingSlash($product, language: 'en');

// Assert URL doesn't end with slash
$this->assertNoTrailingSlash($product);

// Assert URL is accessible (doesn't return 404)
$this->assertUrlIsAccessible($product);

// Assert model has URL for specific language
$this->assertHasUrlForLanguage($product, 'en');
$this->assertHasUrlForLanguage($product, 'bg');

// Assert URL matches expected slug
$this->assertUrlEquals($product, 'test-product');
$this->assertUrlEquals($product, 'test-product', language: 'en');

// Assert slug is unique in database
$this->assertSlugIsUnique('test-product');

// Assert URL contains substring
$this->assertUrlContains($product, 'test');

// Assert URL matches regex pattern
$this->assertUrlMatchesPattern($product, '/^[a-z0-9\-]+$/');

// Assert model has URLs for all configured languages
$this->assertHasUrlsForAllLanguages($product);
```

#### Example Test Cases

```php
public function test_product_url_generation()
{
    $product = Product::factory()->create(['name' => 'Test Product']);

    // Basic assertions
    $this->assertHasUrl($product);
    $this->assertUrlEquals($product, 'test-product');

    // Format assertions
    $this->assertNoLeadingSlash($product);
    $this->assertNoTrailingSlash($product);

    // Multi-language support
    $this->assertHasUrlsForAllLanguages($product);
    $this->assertHasUrlForLanguage($product, 'en');
    $this->assertHasUrlForLanguage($product, 'bg');
}

public function test_product_url_is_accessible()
{
    $product = Product::factory()->create(['name' => 'Accessible Product']);

    $this->assertUrlIsAccessible($product);
}

public function test_category_product_url_structure()
{
    $category = Category::factory()->create(['name' => 'Electronics']);
    $product = Product::factory()->create([
        'name' => 'Laptop',
        'category_id' => $category->id,
    ]);

    // Assert URL contains category name
    $this->assertUrlContains($product, 'electronics');

    // Assert URL matches hierarchical pattern
    $this->assertUrlMatchesPattern($product, '/^electronics\/.+$/');
}

public function test_url_slug_uniqueness()
{
    $product1 = Product::factory()->create(['name' => 'Unique Product']);
    $product2 = Product::factory()->create(['name' => 'Unique Product']);

    // Second product should get a unique slug with suffix
    $this->assertUrlEquals($product1, 'unique-product');
    $this->assertUrlEquals($product2, 'unique-product_1');
}
```

#### Custom Messages

All assertions support custom failure messages:

```php
$this->assertHasUrl(
    $product,
    message: 'Product should have a URL after creation'
);

$this->assertUrlEquals(
    $product,
    'expected-slug',
    message: 'Product URL should match the expected format'
);
```

## TODO

### Planned Features

- [ ] **Pest 4.x Compatibility** - Investigate and fix compatibility issues with Pest 4.1.6+
- [ ] **URL Versioning** - Track URL change history with timestamps and reasons
- [ ] **Sitemap Generation** - Automatic sitemap.xml generation command
- [ ] **URL Analytics** - Track URL hits and redirects for insights
- [ ] **Soft Delete Support** - Handle URLs for soft-deleted models
- [ ] **Custom Redirect Rules** - Support for regex-based redirect patterns
- [ ] **URL Preview/Dry Run** - Preview what URLs would be generated before committing
- [ ] **Duplicate Detection** - Better handling of potential slug conflicts
- [ ] **URL Health Check** - Command to verify all URLs are accessible
- [ ] **Performance Dashboard** - Artisan command showing URL generation statistics
- [ ] **API Documentation** - OpenAPI/Swagger documentation for API methods
- [ ] **GraphQL Support** - Integration with Laravel Lighthouse
- [ ] **Queue Support** - Queue URL generation for large batches
- [ ] **Event System** - Fire events on URL creation, update, and redirect
- [ ] **URL Aliases** - Support multiple URLs pointing to same resource
- [ ] **URL Templates** - Define URL patterns in config
- [ ] **SEO Analyzer** - Check URLs for SEO best practices
- [ ] **Multi-Tenant Support** - Tenant-specific URL handling
- [ ] **URL Expiration** - Support for temporary URLs
- [ ] **Wildcard Routes** - Support for pattern-based dynamic routes

### Nice to Have

- [ ] **Admin UI** - Simple web interface for managing URLs
- [ ] **Import/Export** - Import URLs from CSV/JSON
- [ ] **URL Shortener Integration** - Generate short URLs automatically
- [ ] **Custom Slug Transformers** - Plugin system for custom slug generation
- [ ] **URL Monitoring** - Integration with monitoring tools (Sentry, etc.)

### Documentation

- [ ] Video tutorials for common use cases
- [ ] Migration guide from other URL packages
- [ ] Performance benchmarks
- [ ] Integration examples with popular packages
- [ ] Troubleshooting guide with common errors

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

### Semantic Commit Messages

See how a minor change to your commit message style can make you a better programmer.
Format: `<type>(<scope>): <subject>`

`<scope>` is optional

```
feat: add hat wobble
^--^  ^------------^
|     |
|     +-> Summary in present tense.
|
+-------> Type: chore, docs, feat, fix, refactor, style, or test.
```

More Examples:

- `feat`: (new feature for the user, not a new feature for build script)
- `fix`: (bug fix for the user, not a fix to a build script)
- `docs`: (changes to the documentation)
- `style`: (formatting, missing semi colons, etc; no production code change)
- `refactor`: (refactoring production code, eg. renaming a variable)
- `test`: (adding missing tests, refactoring tests; no production code change)
- `chore`: (updating grunt tasks etc; no production code change)

References:

- https://www.conventionalcommits.org/
- https://seesparkbox.com/foundry/semantic_commit_messages
- http://karma-runner.github.io/1.0/dev/git-commit-msg.html
## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Vladislav Stoitsov](https://github.com/vlados)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
