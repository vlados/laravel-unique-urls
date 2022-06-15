<?php

namespace Vlados\LaravelUniqueUrls;

trait HasUniqueUrlAttributes
{
    public function initializeHasUniqueUrlTrait(): void
    {
        $this->append('relative_url');
        $this->append('absolute_url');
        $this->makeVisible('relative_url');
        $this->makeVisible('absolute_url');
    }

    public function getRelativeUrlAttribute(): string
    {
        return $this->getUrl(false);
    }

    public function getAbsoluteUrlAttribute(): string
    {
        return $this->getUrl(true);
    }

    /**
     * Returns the absolute url for the model.
     *
     * @return string
     *
     * @throws Throwable
     */
    private function getUrl($absolute = true): string
    {
        $url = $this->url()->where('language', app()->getLocale())->first()->slug ?? '';

        return $absolute ? url($url) : $url;
    }
}
