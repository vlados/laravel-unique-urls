<?php

declare(strict_types=1);

namespace Vlados\LaravelUniqueUrls\Contracts;

interface ControllerResolver
{
    /**
     * Resolve a controller name to an object instance.
     *
     * Returns null if the controller cannot be resolved.
     */
    public function resolve(string $controller): ?object;
}
