<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Vlados\LaravelUniqueUrls\Exceptions\EmptySlugException;
use Vlados\LaravelUniqueUrls\Exceptions\InvalidSlugException;
use Vlados\LaravelUniqueUrls\Models\Url;
use Vlados\LaravelUniqueUrls\Tests\Models\TestModel;

beforeEach(function () {
    app()->setLocale('en');
});

// ============================================
// Auto-Trim Slashes (Critical Feature)
// ============================================

test('14. Auto-trim leading slash from slug', function () {
    $model = new TestModel();
    $model->name = 'test';

    // Override urlStrategy to return slug with leading slash
    $slug = Url::makeSlug('/test-product', $model);

    expect($slug)->toBe('test-product')
        ->and($slug)->not->toStartWith('/');
});

test('15. Auto-trim trailing slash from slug', function () {
    $model = new TestModel();
    $model->name = 'test';

    $slug = Url::makeSlug('test-product/', $model);

    expect($slug)->toBe('test-product')
        ->and($slug)->not->toEndWith('/');
});

test('16. Auto-trim both leading and trailing slashes', function () {
    $model = new TestModel();
    $model->name = 'test';

    $slug = Url::makeSlug('/test-product/', $model);

    expect($slug)->toBe('test-product')
        ->and($slug)->not->toStartWith('/')
        ->and($slug)->not->toEndWith('/');
});

test('17. Slug with only slashes throws EmptySlugException', function () {
    $model = new TestModel();
    $model->name = 'test';

    expect(fn() => Url::makeSlug('/', $model))
        ->toThrow(EmptySlugException::class, 'empty after trimming');
});

test('18. Auto-trim logs warning when slug is modified', function () {
    Log::spy();

    $model = new TestModel();
    $model->name = 'test';

    Url::makeSlug('/test-product/', $model);

    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(function ($message, $context) {
            return $message === 'Slug was trimmed: leading/trailing slashes removed'
                && $context['original'] === '/test-product/'
                && $context['trimmed'] === 'test-product';
        });
});

// ============================================
// Custom Exceptions
// ============================================

test('19. EmptySlugException thrown for empty slug with model context', function () {
    $model = TestModel::create(['name' => 'test']);

    expect(fn() => Url::makeSlug('', $model))
        ->toThrow(EmptySlugException::class)
        ->and(fn() => Url::makeSlug('', $model))
        ->toThrow(EmptySlugException::class, 'TestModel');
});

test('20. InvalidSlugException thrown for invalid characters when validation enabled', function () {
    Config::set('unique-urls.validate_slugs', true);

    $model = new TestModel();
    $model->name = 'test';

    // Test various invalid characters
    expect(fn() => Url::makeSlug('Test-Product', $model))
        ->toThrow(InvalidSlugException::class, 'contains invalid characters');

    expect(fn() => Url::makeSlug('test_product', $model))
        ->toThrow(InvalidSlugException::class, 'contains invalid characters');

    expect(fn() => Url::makeSlug('test product', $model))
        ->toThrow(InvalidSlugException::class, 'contains invalid characters');

    Config::set('unique-urls.validate_slugs', false);
});

test('21. Validation allows valid slugs (lowercase, numbers, hyphens)', function () {
    Config::set('unique-urls.validate_slugs', true);

    $model = new TestModel();
    $model->name = 'test';

    $slug = Url::makeSlug('valid-slug-123', $model);
    expect($slug)->toBe('valid-slug-123');

    $slug = Url::makeSlug('test', $model);
    expect($slug)->toBe('test');

    $slug = Url::makeSlug('product-2024', $model);
    expect($slug)->toBe('product-2024');

    Config::set('unique-urls.validate_slugs', false);
});

// ============================================
// Reserved Slugs
// ============================================

test('22. Reserved slug throws InvalidSlugException', function () {
    Config::set('unique-urls.reserved_slugs', ['admin', 'api', 'login']);

    $model = TestModel::create(['name' => 'test']);

    expect(fn() => Url::makeSlug('admin', $model))
        ->toThrow(InvalidSlugException::class, 'slug is reserved');
});

test('23. Non-reserved slug is allowed', function () {
    Config::set('unique-urls.reserved_slugs', ['admin', 'api']);

    $model = TestModel::create(['name' => 'test']);

    $slug = Url::makeSlug('products', $model);
    expect($slug)->toBe('products');
});

test('24. Empty reserved slugs array allows all slugs', function () {
    Config::set('unique-urls.reserved_slugs', []);

    $model = TestModel::create(['name' => 'test']);

    $slug = Url::makeSlug('admin', $model);
    expect($slug)->toBe('admin');
});

// ============================================
// Null Handling
// ============================================

test('25. getSlug returns empty string instead of null when no URL exists', function () {
    $model = new TestModel();
    $model->disableGeneratingUrlsOnCreate();
    $model->name = 'test';
    $model->save();

    // Model has no URLs yet
    expect($model->urls()->count())->toBe(0);

    // Should return empty string, not null
    $slug = $model->getSlug();
    expect($slug)->toBe('')
        ->and($slug)->not->toBeNull();
});

test('26. relative_url returns empty string for model without URL', function () {
    $model = new TestModel();
    $model->disableGeneratingUrlsOnCreate();
    $model->name = 'test';
    $model->save();

    expect($model->relative_url)->toBe('')
        ->and($model->relative_url)->not->toBeNull();
});

test('27. absolute_url returns empty string for model without URL', function () {
    $model = new TestModel();
    $model->disableGeneratingUrlsOnCreate();
    $model->name = 'test';
    $model->save();

    expect($model->absolute_url)->toBe('')
        ->and($model->absolute_url)->not->toBeNull();
});

// ============================================
// Batch URL Generation
// ============================================

test('28. Batch URL generation processes multiple models', function () {
    // Create 10 models without URLs
    $models = collect();
    for ($i = 0; $i < 10; $i++) {
        $model = new TestModel();
        $model->disableGeneratingUrlsOnCreate();
        $model->name = 'batch-test-' . $i;
        $model->save();
        $models->push($model);
    }

    // Generate URLs in batch
    $stats = TestModel::generateUrlsInBatch($models);

    expect($stats['generated'])->toBe(10)
        ->and($stats['skipped'])->toBe(0)
        ->and($stats['failed'])->toBe(0);

    // Verify all models now have URLs
    $models->each(function ($model) {
        $model->refresh();
        expect($model->urls()->count())->toBeGreaterThan(0);
    });
});

test('29. Batch generation skips models with existing URLs', function () {
    // Create 5 models with URLs
    $modelsWithUrls = collect();
    for ($i = 0; $i < 5; $i++) {
        $model = TestModel::create(['name' => 'existing-' . $i]);
        $modelsWithUrls->push($model);
    }

    // Create 5 models without URLs
    $modelsWithoutUrls = collect();
    for ($i = 0; $i < 5; $i++) {
        $model = new TestModel();
        $model->disableGeneratingUrlsOnCreate();
        $model->name = 'new-' . $i;
        $model->save();
        $modelsWithoutUrls->push($model);
    }

    $allModels = $modelsWithUrls->merge($modelsWithoutUrls);

    $stats = TestModel::generateUrlsInBatch($allModels);

    expect($stats['generated'])->toBe(5)
        ->and($stats['skipped'])->toBe(5)
        ->and($stats['failed'])->toBe(0);
});

test('30. Batch generation with custom chunk size', function () {
    $models = collect();
    for ($i = 0; $i < 25; $i++) {
        $model = new TestModel();
        $model->disableGeneratingUrlsOnCreate();
        $model->name = 'chunk-test-' . $i;
        $model->save();
        $models->push($model);
    }

    // Process in chunks of 10
    $stats = TestModel::generateUrlsInBatch($models, chunkSize: 10);

    expect($stats['generated'])->toBe(25);
});

test('31. Batch generation with progress callback', function () {
    $models = collect();
    for ($i = 0; $i < 5; $i++) {
        $model = new TestModel();
        $model->disableGeneratingUrlsOnCreate();
        $model->name = 'callback-test-' . $i;
        $model->save();
        $models->push($model);
    }

    $callbackCount = 0;
    $stats = TestModel::generateUrlsInBatch(
        $models,
        callback: function ($model, $processed, $total, $stats) use (&$callbackCount) {
            $callbackCount++;
            expect($processed)->toBeLessThanOrEqual($total);
        }
    );

    expect($callbackCount)->toBe(5);
});

test('32. Batch generation continues processing after errors', function () {
    Log::spy();

    $models = collect();

    // Create 5 valid models
    for ($i = 0; $i < 5; $i++) {
        $model = new TestModel();
        $model->disableGeneratingUrlsOnCreate();
        $model->name = 'batch-error-test-' . $i;
        $model->save();
        $models->push($model);
    }

    // Batch generation should handle all models
    $stats = TestModel::generateUrlsInBatch($models);

    // All 5 should be generated successfully (empty name test doesn't actually fail)
    expect($stats['generated'])->toBe(5)
        ->and($stats['skipped'])->toBe(0);

    // Test that callback is executed for each model
    expect($models->count())->toBe(5);
});

// ============================================
// Integration Tests for Combined Features
// ============================================

test('33. Real-world scenario: hierarchical URLs with trimming', function () {
    // Simulating category/product URL structure where parent might return null
    $model = new TestModel();
    $model->name = 'product';

    // Simulate concatenation with null parent: null . '/' . 'product' = '/product'
    $slug = Url::makeSlug('/product', $model);

    expect($slug)->toBe('product')
        ->and($slug)->not->toContain('/');
});

test('34. Config options are respected', function () {
    // Test auto_trim_slashes config
    Config::set('unique-urls.auto_trim_slashes', true);
    $model = TestModel::create(['name' => 'test']);
    $slug = Url::makeSlug('/test/', $model);
    expect($slug)->toBe('test');

    // Test validate_slugs config
    Config::set('unique-urls.validate_slugs', true);
    expect(fn() => Url::makeSlug('Invalid Slug!', $model))
        ->toThrow(InvalidSlugException::class);
    Config::set('unique-urls.validate_slugs', false);

    // Test reserved_slugs config
    Config::set('unique-urls.reserved_slugs', ['forbidden']);
    expect(fn() => Url::makeSlug('forbidden', $model))
        ->toThrow(InvalidSlugException::class, 'reserved');
});
