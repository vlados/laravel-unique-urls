<?php

namespace Vlados\LaravelUniqueUrls;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Vlados\LaravelUniqueUrls\Models\Url;

trait HasUniqueUrlTrait
{
    protected static function bootHasUniqueUrlTrait(): void
    {
        static::creating(function (Model $model) {
            $model->generateUrlOnCreate();
        });

        static::updating(function (Model $model) {
            $model->generateUrlOnUpdate();
        });
    }

    protected function generateUrlOnCreate(): void
    {
        $slug = $this->urlStrategy();
        dd($slug);
//        $this->addSlug();
    }

    protected function generateUrlOnUpdate(): void
    {
        $this->addSlug();
    }

    public function url()
    {
        return $this->morphOne(Url::class, 'related');
    }

    /**
     * Returns the absolute url for the model
     * @return string
     * @throws \Throwable
     */
    public function getSlugAttribute(): string
    {
        throw_if(is_null($this->url), 'The model has no generated url');

        return url($this->url->where('language', app()->getLocale())->first()->slug);
    }

    public function urlStrategy(): string
    {
        return Str::slug($this->getAttribute('name'));
    }
}
