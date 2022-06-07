<?php

namespace Vlados\LaravelUniqueUrls;

use Closure;

interface HasUniqueUrlInterface
{
    public function url();

    public function getSlugAttribute();

    /*
     * Strategy for generating the unique url
     */
    public function urlStrategy(Closure $closure);
}
