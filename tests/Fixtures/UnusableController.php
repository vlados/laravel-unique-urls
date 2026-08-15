<?php

namespace Vlados\LaravelUniqueUrls\Tests\Fixtures;

/**
 * A controller with none of the methods the request handler can dispatch to:
 * no __invoke(), no show(), no index() and no matching declared method.
 */
class UnusableController
{
    public function somethingElse(): string
    {
        return 'nope';
    }
}
