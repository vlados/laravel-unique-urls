# Bulk Operations

## Disabling Auto-Generation Per Instance

```php
$model = new Product();
$model->disableGeneratingUrlsOnCreate();
$model->name = 'Test';
$model->save(); // no URL generated
```

## Overriding `isAutoGenerateUrls()`

Return `false` from the model to permanently disable auto-generation:

```php
public function isAutoGenerateUrls(): bool
{
    return false;
}
```

Generate URLs manually afterward:

```php
$model->generateUrl();
```

## Global Toggle

Disable URL generation for **all** models at once — useful during seeders, imports, or migrations:

```php
// Disable globally
Product::disableUrlGeneration();

// Re-enable
Product::enableUrlGeneration();

// Scoped callback (re-enables automatically)
Product::withoutGeneratingUrls(function () {
    Product::factory()->count(500)->create();
});
```

## `generateUrlsInBatch()`

Process large collections with built-in chunking, garbage collection, and error handling:

```php
use App\Models\Product;

// Basic
$products = Product::whereDoesntHave('urls')->get();
$stats = Product::generateUrlsInBatch($products);
// ['generated' => 150, 'skipped' => 0, 'failed' => 0]

// Custom chunk size
$stats = Product::generateUrlsInBatch($products, chunkSize: 100);

// Progress callback
$stats = Product::generateUrlsInBatch(
    $products,
    chunkSize: 500,
    callback: function ($model, $processed, $total, $stats) {
        if ($processed % 100 === 0) {
            Log::info("Progress: {$processed}/{$total}", $stats);
        }
    }
);
```

### With Artisan Progress Bar

```php
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

## Performance Tips

### Use chunking for large datasets

```bash
php artisan urls:generate --model=Product --chunk-size=100
```

### Generate only missing URLs

```bash
php artisan urls:generate --only-missing
```

### Disable during imports

```php
Product::withoutGeneratingUrls(function () {
    // bulk import logic
});

// then generate all at once
php artisan urls:generate --model=Product --only-missing
```

### Add database indexes

```php
Schema::table('urls', function (Blueprint $table) {
    $table->index('slug');
    $table->index(['related_type', 'related_id']);
    $table->index('language');
});
```

### Cache frequently accessed URLs

```php
$url = Cache::remember(
    "product_url_{$product->id}",
    now()->addHour(),
    fn () => $product->relative_url
);
```
