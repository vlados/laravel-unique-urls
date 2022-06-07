<?php

namespace Vlados\LaravelUniqueUrls;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Vlados\LaravelUniqueUrls\Models\Url;

/**
 * @property bool $autoGenerateUrls
 */
trait HasUniqueUrlTrait
{
    abstract public function getUrlHandler();

    private bool $autoGenerateUrls = true;

    protected static function bootHasUniqueUrlTrait(): void
    {
        static::created(function (Model $model) {
            if ($model->isAutoGenerateUrls() === false) {
                return;
            }
            $model->generateUrl();
        });

        static::updated(function (Model $model) {
            if ($model->isAutoGenerateUrls() === false) {
                return;
            }
            $model->generateUrlOnUpdate();
        });
    }

    /**
     * @throws \Exception
     */
    public function generateUrl(): void
    {
        $unique_url = Url::makeSlug($this->urlStrategy(), $this);
        $urls = [];
        foreach (config('unique-urls.languages') as $lang) {
            $prefix = (config('app.fallback_locale') == $lang) ? '' : $lang . '/';
            $new_url = $this->getUrlHandler();
            $new_url['language'] = $lang;
            $new_url['slug'] = $prefix . $unique_url;
            $urls[] = $new_url;
        }
        if (count($urls)) {
            $this->url()->createMany($urls);
        }
    }

    protected function generateUrlOnUpdate(): void
    {
        $unique_url = Url::makeSlug($this->urlStrategy(), $this);
        $this->url()->get()->each(function (Url $url) use ($unique_url) {
            $prefix = (config('app.fallback_locale') == $url->getAttribute('language')) ? '' : $url->getAttribute('language') . '/';
            if ($url->getAttribute('slug') === $prefix . $unique_url) {
                return;
            }

            $url->update([
                'slug' => $prefix . $unique_url,
            ]);
            $url->save();
        });
    }

    public function url(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(Url::class, 'related');
    }

    /**
     * Returns the absolute url for the model
     * @return string
     * @throws \Throwable
     */
    public function getUrl($absolute = true): string
    {
        throw_if(is_null($this->url), 'The model has no generated url');

        $url = $this->url()->where('language', app()->getLocale())->first()->slug;

        return $absolute ? url($url) : $url;
    }

    public function urlStrategy(): string
    {
        return Str::slug($this->getAttribute('name'));
    }

    /**
     * Generate automatically the urls on create. You can disable it and manually trigger it after
     * @return bool
     */
    public function isAutoGenerateUrls(): bool
    {
        return $this->autoGenerateUrls;
    }

    /**
     * @param bool $autoGenerateUrls
     */
    public function setAutoGenerateUrls(bool $autoGenerateUrls): void
    {
        $this->autoGenerateUrls = $autoGenerateUrls;
    }
}
