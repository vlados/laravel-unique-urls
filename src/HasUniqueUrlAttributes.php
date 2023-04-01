<?php

declare(strict_types=1);

namespace Vlados\LaravelUniqueUrls;

trait HasUniqueUrlAttributes
{
    public function initializeHasUniqueUrlAttributes(): void
    {
        $this->append('relative_url');
        $this->makeVisible('relative_url');
    }

    public function getRelativeUrlAttribute(): string|null
    {
        return $this->getSlug(null, true);
    }

    public function getAbsoluteUrlAttribute(): string|null
    {
        return $this->getSlug(null, false);
    }

    /**
     * Returns the absolute url for the model.
     *
     * @param bool $relative Return absolute or relative url
     *
     * @return string
     */
    public function getSlug(?string $language = '', bool $relative = true): string|null
    {
        $language = $language ? $language : app()->getLocale();
        if ($this->urls->isEmpty()) {
            $this->load('urls');
        }
        $url = $this->urls->where('language', $language)->first();
        if ($url) {
            return $relative ? $url->slug : url($url->slug);
        }

        return null;
    }
}
