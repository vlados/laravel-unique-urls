<?php

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

    abstract public function urlHandler();

    /**
     * @throws Exception
     */
    public function generateUrl(): void
    {
//        $this->loadMissing(['urls']);
        $createRecords = [];

        $existing_languages = $this->urls->keyBy('language');
        foreach (config('unique-urls.languages') as $locale => $lang) {
            $unique_url = Url::makeSlug($this->urlStrategy($lang, $locale), $this);

            $new_url = $this->urlHandler();

            if ($existing_languages->has($lang)) {
                // the url is existing for this model
                if ($existing_languages[$lang]->slug !== $unique_url) {
                    // update the existing record if the url slug is different
                    $existing_languages[$lang]['slug'] = $unique_url;
                    $existing_languages[$lang]->save();
                }

                continue;
            }
            $new_url['language'] = $lang;
            $new_url['slug'] = $unique_url;
            $createRecords[] = $new_url;
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
        static::created(function (Model $model) {
            if ($model->isAutoGenerateUrls() === true) {
                $model->generateUrl();
            }
        });

        static::updated(function (Model $model) {
            if ($model->isAutoGenerateUrls() === true) {
                $model->generateUrl();
            }
        });
        static::deleting(function (Model $model) {
            $model->urls()->delete();
        });
    }

}
