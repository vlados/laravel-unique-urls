<?php

namespace Vlados\LaravelUniqueUrls;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use Vlados\LaravelUniqueUrls\Models\Url;

/**
 * @property bool $autoGenerateUrls
 */
trait HasUniqueUrlTrait
{
    private bool $autoGenerateUrls = true;

    abstract public function urlHandler();

    public function initializeHasUniqueUrlTrait(): void
    {
        $this->append('relative_url');
        $this->makeVisible('relative_url');
    }

    /**
     * @throws Exception
     */
    public function generateUrl(): void
    {
        $this->loadMissing('urls');
        $createRecords = [];

        $existing_languages = is_null($this->urls) ? collect() : $this->urls()->get()->keyBy('language');
        foreach (config('unique-urls.languages') as $locale => $lang) {
            $unique_url = Url::makeSlug($this->urlStrategy($lang, $locale), $this);

            $new_url = $this->urlHandler();

            if (in_array($lang, $existing_languages->keys()->toArray())) {
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
        return Str::slug($this->getAttribute('name'));
    }

    public function isAutoGenerateUrls(): bool
    {
        return $this->autoGenerateUrls;
    }

    public function setAutoGenerateUrls(bool $autoGenerateUrls): void
    {
        $this->autoGenerateUrls = $autoGenerateUrls;
    }

    public function getRelativeUrlAttribute(): string
    {
        return $this->getSlug(null, true);
    }

    public function getAbsoluteUrlAttribute(): string
    {
        return $this->getSlug(null, false);
    }

    protected static function bootHasUniqueUrlTrait(): void
    {
        static::created(function (Model $model) {
            if ($model->isAutoGenerateUrls() === true) {
                $model->generateUrl();
            }
        });

        static::updated(function (Model $model) {
            if ($model->isAutoGenerateUrls() === true) {
                $model->generateUrlOnUpdate();
            }
        });
        static::deleting(function (Model $model) {
            $model->urls()->delete();
        });
    }

    protected function generateUrlOnUpdate(): void
    {
        $unique_url = Url::makeSlug($this->urlStrategy(), $this);
        $this->urls()->get()->each(function (Url $url) use ($unique_url) {
            $prefix = config('app.fallback_locale') === $url->getAttribute('language') ? '' : $url->getAttribute('language') . '/';
            if ($url->getAttribute('slug') !== $prefix . $unique_url) {
                $url->update([
                    'slug' => $prefix . $unique_url,
                ]);
                $url->save();
            }
        });
    }

    /**
     * Returns the absolute url for the model.
     *
     * @param string|null $language
     * @param bool $relative Return absolute or relative url
     * @return string
     */
    public function getSlug(?string $language = '', bool $relative = true): string
    {
        $language = $language ?: app()->getLocale();
        if ($this->urls->isEmpty()) {
            $this->load('urls');
        }
        $url = $this->urls->where('language', $language)->first();
        if (is_null($url)) {
            dd("error", $this->urls, $language);
        }

        return $relative ? $url->slug : url($url->slug);
    }
}
