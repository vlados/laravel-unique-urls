<?php

declare(strict_types=1);

namespace Vlados\LaravelUniqueUrls\Testing;

use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Assert as PHPUnit;
use Vlados\LaravelUniqueUrls\Models\Url;

/**
 * Testing assertions for Laravel Unique URLs package.
 *
 * Usage in your test:
 * use Vlados\LaravelUniqueUrls\Testing\AssertsUniqueUrls;
 *
 * class MyTest extends TestCase
 * {
 *     use AssertsUniqueUrls;
 *
 *     public function test_product_has_url()
 *     {
 *         $product = Product::factory()->create();
 *         $this->assertHasUrl($product);
 *     }
 * }
 */
trait AssertsUniqueUrls
{
    /**
     * Assert that a model has at least one URL.
     */
    protected function assertHasUrl(Model $model, ?string $message = null): void
    {
        $message = $message ?? sprintf(
            'Failed asserting that %s (ID: %s) has a URL.',
            get_class($model),
            $model->getKey()
        );

        PHPUnit::assertTrue(
            $model->urls()->exists(),
            $message
        );
    }

    /**
     * Assert that a model does not have any URLs.
     */
    protected function assertHasNoUrl(Model $model, ?string $message = null): void
    {
        $message = $message ?? sprintf(
            'Failed asserting that %s (ID: %s) has no URLs.',
            get_class($model),
            $model->getKey()
        );

        PHPUnit::assertFalse(
            $model->urls()->exists(),
            $message
        );
    }

    /**
     * Assert that a model's URL does not have leading slashes.
     */
    protected function assertNoLeadingSlash(Model $model, ?string $language = null, ?string $message = null): void
    {
        $language = $language ?? app()->getLocale();
        $url = $model->urls()->where('language', $language)->first();

        if (! $url) {
            PHPUnit::fail(sprintf(
                'Model %s (ID: %s) has no URL for language "%s".',
                get_class($model),
                $model->getKey(),
                $language
            ));
        }

        $message = $message ?? sprintf(
            'Failed asserting that URL "%s" for %s (ID: %s) does not start with a slash.',
            $url->slug,
            get_class($model),
            $model->getKey()
        );

        PHPUnit::assertStringStartsNotWith('/', $url->slug, $message);
    }

    /**
     * Assert that a model's URL does not have trailing slashes.
     */
    protected function assertNoTrailingSlash(Model $model, ?string $language = null, ?string $message = null): void
    {
        $language = $language ?? app()->getLocale();
        $url = $model->urls()->where('language', $language)->first();

        if (! $url) {
            PHPUnit::fail(sprintf(
                'Model %s (ID: %s) has no URL for language "%s".',
                get_class($model),
                $model->getKey(),
                $language
            ));
        }

        $message = $message ?? sprintf(
            'Failed asserting that URL "%s" for %s (ID: %s) does not end with a slash.',
            $url->slug,
            get_class($model),
            $model->getKey()
        );

        PHPUnit::assertStringEndsNotWith('/', $url->slug, $message);
    }

    /**
     * Assert that a model's URL is accessible (doesn't return 404).
     */
    protected function assertUrlIsAccessible(Model $model, ?string $language = null, ?string $message = null): void
    {
        $language = $language ?? app()->getLocale();
        $url = $model->urls()->where('language', $language)->first();

        if (! $url) {
            PHPUnit::fail(sprintf(
                'Model %s (ID: %s) has no URL for language "%s".',
                get_class($model),
                $model->getKey(),
                $language
            ));
        }

        $message = $message ?? sprintf(
            'Failed asserting that URL "/%s" is accessible for %s (ID: %s).',
            $url->slug,
            get_class($model),
            $model->getKey()
        );

        $response = $this->get('/' . $url->slug);
        $response->assertSuccessful($message);
    }

    /**
     * Assert that a model has a URL for a specific language.
     */
    protected function assertHasUrlForLanguage(Model $model, string $language, ?string $message = null): void
    {
        $message = $message ?? sprintf(
            'Failed asserting that %s (ID: %s) has a URL for language "%s".',
            get_class($model),
            $model->getKey(),
            $language
        );

        PHPUnit::assertTrue(
            $model->urls()->where('language', $language)->exists(),
            $message
        );
    }

    /**
     * Assert that a model's URL matches a specific slug.
     */
    protected function assertUrlEquals(Model $model, string $expectedSlug, ?string $language = null, ?string $message = null): void
    {
        $language = $language ?? app()->getLocale();
        $url = $model->urls()->where('language', $language)->first();

        if (! $url) {
            PHPUnit::fail(sprintf(
                'Model %s (ID: %s) has no URL for language "%s".',
                get_class($model),
                $model->getKey(),
                $language
            ));
        }

        $message = $message ?? sprintf(
            'Failed asserting that URL for %s (ID: %s) equals "%s". Actual: "%s".',
            get_class($model),
            $model->getKey(),
            $expectedSlug,
            $url->slug
        );

        PHPUnit::assertEquals($expectedSlug, $url->slug, $message);
    }

    /**
     * Assert that a URL slug is unique in the database.
     */
    protected function assertSlugIsUnique(string $slug, ?string $message = null): void
    {
        $count = Url::where('slug', $slug)->count();

        $message = $message ?? sprintf(
            'Failed asserting that slug "%s" is unique. Found %d occurrences.',
            $slug,
            $count
        );

        PHPUnit::assertEquals(1, $count, $message);
    }

    /**
     * Assert that a model's URL contains a specific substring.
     */
    protected function assertUrlContains(Model $model, string $needle, ?string $language = null, ?string $message = null): void
    {
        $language = $language ?? app()->getLocale();
        $url = $model->urls()->where('language', $language)->first();

        if (! $url) {
            PHPUnit::fail(sprintf(
                'Model %s (ID: %s) has no URL for language "%s".',
                get_class($model),
                $model->getKey(),
                $language
            ));
        }

        $message = $message ?? sprintf(
            'Failed asserting that URL "%s" for %s (ID: %s) contains "%s".',
            $url->slug,
            get_class($model),
            $model->getKey(),
            $needle
        );

        PHPUnit::assertStringContainsString($needle, $url->slug, $message);
    }

    /**
     * Assert that a model's URL matches a specific pattern.
     */
    protected function assertUrlMatchesPattern(Model $model, string $pattern, ?string $language = null, ?string $message = null): void
    {
        $language = $language ?? app()->getLocale();
        $url = $model->urls()->where('language', $language)->first();

        if (! $url) {
            PHPUnit::fail(sprintf(
                'Model %s (ID: %s) has no URL for language "%s".',
                get_class($model),
                $model->getKey(),
                $language
            ));
        }

        $message = $message ?? sprintf(
            'Failed asserting that URL "%s" for %s (ID: %s) matches pattern "%s".',
            $url->slug,
            get_class($model),
            $model->getKey(),
            $pattern
        );

        PHPUnit::assertMatchesRegularExpression($pattern, $url->slug, $message);
    }

    /**
     * Assert that a model has URLs for all configured languages.
     */
    protected function assertHasUrlsForAllLanguages(Model $model, ?string $message = null): void
    {
        $configuredLanguages = array_values(config('unique-urls.languages', []));
        $modelLanguages = $model->urls()->pluck('language')->toArray();

        $missingLanguages = array_diff($configuredLanguages, $modelLanguages);

        $message = $message ?? sprintf(
            'Failed asserting that %s (ID: %s) has URLs for all languages. Missing: %s',
            get_class($model),
            $model->getKey(),
            implode(', ', $missingLanguages)
        );

        PHPUnit::assertEmpty($missingLanguages, $message);
    }
}
