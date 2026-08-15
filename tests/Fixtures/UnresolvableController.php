<?php

namespace Vlados\LaravelUniqueUrls\Tests\Fixtures;

/**
 * The class exists, but the container cannot build it: resolving a controller
 * is not the same as checking that its class is there.
 */
class UnresolvableController
{
    public function __construct(private MissingDependency $dependency)
    {
    }

    public function show(): string
    {
        return $this->dependency->value();
    }
}
