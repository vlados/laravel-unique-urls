<?php

declare(strict_types=1);

namespace Vlados\LaravelUniqueUrls\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Vlados\LaravelUniqueUrls\LaravelUniqueUrlsController
 */
class LaravelUniqueUrls extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'laravel-unique-urls';
    }
}
