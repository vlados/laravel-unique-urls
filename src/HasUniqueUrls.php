<?php

declare(strict_types=1);

namespace Vlados\LaravelUniqueUrls;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Vlados\LaravelUniqueUrls\Models\Url;

trait HasUniqueUrls
{
    use HasUniqueUrlAttributes;

    private bool $autoGenerateUrls = true;

    abstract public function urlHandler(): array;

    /**
     * Initialize the HasUniqueUrls trait for an instance.
     *
     * @throws Exception
     */
    public function initializeHasUniqueUrls(): void
    {
        $this->checkForConflictingAttributes();
    }

    /**
     * Check if the model has conflicting 'url' or 'urls' attributes.
     *
     * @throws Exception
     */
    private function checkForConflictingAttributes(): void
    {
        $conflictingAttributes = ['url', 'urls'];
        $modelClass = get_class($this);

        foreach ($conflictingAttributes as $attribute) {
            // Check if attribute exists in fillable, guarded, or as a database column
            if ($this->hasColumn($attribute)) {
                throw new Exception(
                    "Model [{$modelClass}] has a conflicting column '{$attribute}'. " .
                    "The HasUniqueUrls trait uses 'urls' as a relationship name and provides 'relative_url' and 'absolute_url' attributes. " .
                    "Please rename the '{$attribute}' column in your model to avoid conflicts."
                );
            }
        }
    }

    /**
     * Check if the model has a specific column.
     */
    private function hasColumn(string $column): bool
    {
        try {
            // Check if the table exists and has the column
            if ($this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), $column)) {
                return true;
            }
        } catch (\Exception $e) {
            // If we can't check the schema (e.g., during testing without migrations), skip the check
        }

        return false;
    }

    /**
     * Generate a unique URL for the model.
     *
     * @throws Exception
     */
    public function generateUrl(): void
    {
        $this->load('urls');
        $createRecords = [];

        $existingLanguages = $this->urls->keyBy('language');

        foreach (config('unique-urls.languages') as $locale => $lang) {
            $uniqueUrl = Url::makeSlug($this->urlStrategy($lang, $locale), $this);
            $newUrl = $this->urlHandler();

            $this->handleExistingUrl($existingLanguages, $lang, $uniqueUrl);

            if (! $existingLanguages->has($lang)) {
                $newUrl['language'] = $lang;
                $newUrl['slug'] = $uniqueUrl;
                $createRecords[] = $newUrl;
            }
        }

        if (count($createRecords)) {
            $this->urls()->createMany($createRecords);
        }
    }

    /**
     * Generate URLs for multiple models in batch with memory optimization.
     *
     * @param  \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Collection  $models
     * @param  int  $chunkSize  Number of models to process before garbage collection
     * @param  callable|null  $callback  Optional callback for progress tracking
     * @return array Statistics about the operation ['generated' => int, 'skipped' => int, 'failed' => int]
     */
    public static function generateUrlsInBatch($models, int $chunkSize = 500, ?callable $callback = null): array
    {
        $stats = ['generated' => 0, 'skipped' => 0, 'failed' => 0];
        $processed = 0;

        foreach ($models->chunk($chunkSize) as $chunk) {
            foreach ($chunk as $model) {
                try {
                    // Check if URLs already exist
                    if ($model->urls()->exists()) {
                        $stats['skipped']++;
                    } else {
                        $model->generateUrl();
                        $stats['generated']++;
                    }
                } catch (\Throwable $e) {
                    $stats['failed']++;
                    // Log the error but continue processing
                    Log::error('Failed to generate URL in batch', [
                        'model' => get_class($model),
                        'id' => $model->getKey(),
                        'error' => $e->getMessage(),
                    ]);
                }

                $processed++;

                // Call progress callback if provided
                if ($callback) {
                    $callback($model, $processed, $models->count(), $stats);
                }
            }

            // Force garbage collection after each chunk
            gc_collect_cycles();
        }

        return $stats;
    }

    public function urls(): MorphMany
    {
        return $this->morphMany(Url::class, 'related');
    }

    public function urlStrategy($language, $locale): string
    {
        return Str::slug($this->getTranslation('name', $language), '-', $locale);
    }

    public function isAutoGenerateUrls(): bool
    {
        return $this->autoGenerateUrls;
    }

    public function disableGeneratingUrlsOnCreate(): void
    {
        $this->autoGenerateUrls = false;
    }

    protected static function bootHasUniqueUrls(): void
    {
        static::created(static function (Model $model): void {
            if ($model->isAutoGenerateUrls()) {
                $model->generateUrl();
            }
        });

        static::updated(static function (Model $model): void {
            if ($model->isAutoGenerateUrls()) {
                $model->generateUrl();
            }
        });

        static::deleting(static function (Model $model): void {
            $model->urls()->delete();
        });
    }

    private function handleExistingUrl($existingLanguages, string $lang, string $uniqueUrl): void
    {
        if ($existingLanguages->has($lang) && $existingLanguages[$lang]->slug !== $uniqueUrl) {
            $existingLanguages[$lang]['slug'] = $uniqueUrl;
            $existingLanguages[$lang]->save();
        }
    }
}
