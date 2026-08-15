<?php

namespace Vlados\LaravelUniqueUrls\Tests\Fixtures;

/**
 * Deliberately never bound in the container, so anything depending on it
 * cannot be built.
 */
interface MissingDependency
{
    public function value(): string;
}
