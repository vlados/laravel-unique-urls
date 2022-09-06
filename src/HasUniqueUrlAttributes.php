<?php

namespace Vlados\LaravelUniqueUrls;

trait HasUniqueUrlAttributes
{
    public function initializeHasUniqueUrlAttributes(): void
    {
        $this->append('relative_url');
        $this->makeVisible('relative_url');
    }

    public function getRelativeUrlAttribute(): string
    {
        return $this->getSlug(null, true);
    }

    public function getAbsoluteUrlAttribute(): string
    {
        return $this->getSlug(null, false);
    }

    /**
     * Returns the absolute url for the model.
     *
     * @param string|null $language
     * @param bool $relative Return absolute or relative url
     *
     * @return string
     */
    public function getSlug(?string $language = '', bool $relative = true): string
    {
        $language = $language ? $language : app()->getLocale();
        if ($this->urls->isEmpty()) {
            $this->load('urls');
        }
        $url = $this->urls->where('language', $language)->first();

        return $relative ? $url->slug : url($url->slug);
    }
}
