# Usage

## Model Setup

Add the `HasUniqueUrls` trait and implement two methods:

```php
use Vlados\LaravelUniqueUrls\HasUniqueUrls;

class Product extends Model
{
    use HasUniqueUrls;

    public function urlStrategy($language, $locale): string
    {
        return Str::slug($this->getAttribute('name'), '-', $locale);
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

### urlHandler Keys

| Key | Description |
|-----|-------------|
| `controller` | FQCN of the controller (or Livewire component name) |
| `method` | Controller method to call (leave empty `''` for Livewire) |
| `arguments` | Extra data passed to the controller method / `mount()` |

The handler method receives the request and arguments:

```php
public function view(Request $request, $arguments = [])
{
    // $arguments['related'] contains the originating model
}
```

## Catch-All Route

Register this route **at the end** of `routes/web.php` so it doesn't shadow other routes:

```php
Route::get('{urlObj}', [\Vlados\LaravelUniqueUrls\LaravelUniqueUrlsController::class, 'handleRequest'])
    ->where('urlObj', '.*');
```

## Getting URLs

```php
// Relative URL for current locale
$model->relative_url; // "my-product-name"

// Absolute URL for current locale
$model->absolute_url; // "https://example.com/my-product-name"

// Specific language (relative)
$model->getSlug('en', true); // "my-product-name"

// Specific language (absolute)
$model->getSlug('en', false); // "https://example.com/my-product-name"

// Default (current app locale)
$model->getSlug(); // "my-product-name"

// All active URL records
$model->urls; // Collection of Url models
```

## Multi-Language URLs

Configure languages in `config/unique-urls.php`:

```php
'languages' => [
    'bg_BG' => 'bg',
    'en_US' => 'en',
    'de_DE' => 'de',
],
```

The `urlStrategy()` method receives both `$language` and `$locale`, so you can generate truly different slugs per language (not just a prefix):

```php
public function urlStrategy($language, $locale): string
{
    return Str::slug($this->getTranslation('name', $language), '-', $locale);
}
```

## Hierarchical URLs

Build parent/child URL structures by referencing a parent model's slug:

```php
public function urlStrategy($language, $locale): string
{
    $parentSlug = $this->category?->getSlug($language);

    return $parentSlug . '/' . Str::slug($this->name, '-', $locale);
}
// Result: "electronics/laptop"
```

## Automatic Redirects

When a model's URL changes, the old slug is automatically preserved as a redirect (HTTP 301 by default). Redirect chains are collapsed — visiting any historical URL redirects straight to the current one.

Disable redirects globally in config:

```php
'create_redirects' => false,
```

## Slug Uniqueness

If a generated slug already exists, the package appends `_1`, `_2`, etc. until a unique slug is found:

```
my-product       (first)
my-product_1     (second with same name)
my-product_2     (third)
```

## Livewire Integration

### FQCN-based Components

Set `method` to an empty string and point `controller` to the Livewire component class:

```php
public function urlHandler(): array
{
    return [
        'controller' => ViewCategory::class,
        'method'     => '',
        'arguments'  => [],
    ];
}
```

The component receives the URL model in `mount()`:

```php
class ViewCategory extends Component
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

### SFC Component Names (v2.0+)

Single File Components use kebab-case names instead of FQCNs:

```php
public function urlHandler(): array
{
    return [
        'controller' => 'view-category', // kebab-case component name
        'method'     => '',
        'arguments'  => [],
    ];
}
```

The package detects names without backslashes and resolves them via `app('livewire')->new()`.
