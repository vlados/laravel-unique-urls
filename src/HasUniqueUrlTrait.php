<?php

namespace Vlados\LaravelUniqueUrls;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Vlados\LaravelUniqueUrls\Models\Url;

trait HasUniqueUrlTrait
{
    abstract public function getUrlHandler();

    protected static function bootHasUniqueUrlTrait(): void
    {
        static::created(function (Model $model) {
            $model->generateUrlOnCreate();
        });

        static::updated(function (Model $model) {
            $model->generateUrlOnUpdate();
        });
    }

    /**
     * @throws \Exception
     */
    protected function generateUrlOnCreate(): void
    {
        $unique_url = Url::makeSlug($this->urlStrategy(), $this);
        $urls = [];
        foreach (config('unique-urls.languages') as $lang) {
            $prefix = (config('app.fallback_locale') == $lang) ? '' : $lang.'/';
            $new_url = $this->getUrlHandler();
            $new_url['language'] = $lang;
            $new_url['slug'] = $prefix.$unique_url;
            $urls[] = $new_url;
        }
        $this->url()->createMany($urls);
    }

    protected function generateUrlOnUpdate(): void
    {
        $unique_url = Url::makeSlug($this->urlStrategy(), $this);
        $this->url()->get()->each(function (Url $url) use ($unique_url) {
            $prefix = (config('app.fallback_locale') == $url->getAttribute('language')) ? '' : $url->getAttribute('language').'/';
//            $redirect_url = $url->replicate();
//            $redirect_url->controller = LaravelUniqueUrls::class;
//            $redirect_url->related = null;
//            $redirect_url->method = 'handleRedirect';
//            $redirect_url->arguments = [
//                'original_model' => $url->getAttribute('related_type'),
//                'original_id' => $url->getAttribute('related_id'),
//                'redirect_to' => $prefix.$unique_url,
//            ];
            $url->update([
                'slug' => $prefix.$unique_url,
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
}
