<?php

namespace Vlados\LaravelUniqueUrls\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Vlados\LaravelUniqueUrls\LaravelUniqueUrls
 */
class LaravelUniqueUrls extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'laravel-unique-urls';
    }
}
