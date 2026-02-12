# Testing

## Setup

Add the `AssertsUniqueUrls` trait to your test class:

```php
use Tests\TestCase;
use Vlados\LaravelUniqueUrls\Testing\AssertsUniqueUrls;

class ProductTest extends TestCase
{
    use AssertsUniqueUrls;
}
```

## Assertions

| Assertion | Description |
|-----------|-------------|
| `assertHasUrl($model)` | Model has at least one URL |
| `assertHasNoUrl($model)` | Model has no URLs |
| `assertNoLeadingSlash($model, ?$language)` | URL does not start with `/` |
| `assertNoTrailingSlash($model)` | URL does not end with `/` |
| `assertUrlIsAccessible($model)` | URL does not return 404 |
| `assertHasUrlForLanguage($model, $lang)` | URL exists for the given language |
| `assertUrlEquals($model, $slug, ?$language)` | URL matches the expected slug |
| `assertSlugIsUnique($slug)` | Slug appears only once in the database |
| `assertUrlContains($model, $substring)` | URL contains the given substring |
| `assertUrlMatchesPattern($model, $regex)` | URL matches a regex pattern |
| `assertHasUrlsForAllLanguages($model)` | URLs exist for every configured language |

## Example Test Cases

```php
public function test_product_url_generation()
{
    $product = Product::factory()->create(['name' => 'Test Product']);

    $this->assertHasUrl($product);
    $this->assertUrlEquals($product, 'test-product');
    $this->assertNoLeadingSlash($product);
    $this->assertNoTrailingSlash($product);
    $this->assertHasUrlsForAllLanguages($product);
}

public function test_hierarchical_url_structure()
{
    $category = Category::factory()->create(['name' => 'Electronics']);
    $product = Product::factory()->create([
        'name' => 'Laptop',
        'category_id' => $category->id,
    ]);

    $this->assertUrlContains($product, 'electronics');
    $this->assertUrlMatchesPattern($product, '/^electronics\/.+$/');
}

public function test_slug_uniqueness()
{
    $product1 = Product::factory()->create(['name' => 'Unique Product']);
    $product2 = Product::factory()->create(['name' => 'Unique Product']);

    $this->assertUrlEquals($product1, 'unique-product');
    $this->assertUrlEquals($product2, 'unique-product_1');
}
```

## Custom Failure Messages

All assertions accept an optional `message` parameter:

```php
$this->assertHasUrl($product, message: 'Product should have a URL after creation');
$this->assertUrlEquals($product, 'expected-slug', message: 'Slug should match');
```
