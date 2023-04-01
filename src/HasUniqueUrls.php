<?php

declare(strict_types=1);

namespace Vlados\LaravelUniqueUrls;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use Vlados\LaravelUniqueUrls\Models\Url;

trait HasUniqueUrls
{
    use HasUniqueUrlAttributes;

    private bool $autoGenerateUrls = true;

    abstract public function urlHandler(): array;

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
