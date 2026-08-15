<?php

namespace Vlados\LaravelUniqueUrls\Tests\Fixtures;

/**
 * A controller that only implements show(). LaravelUniqueUrlsController falls
 * back to show() when the declared method does not exist, so this is a valid
 * handler at runtime.
 */
class ShowOnlyController
{
    public function show(): string
    {
        return 'shown';
    }
}
