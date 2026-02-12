<?php

declare(strict_types=1);

namespace Vlados\LaravelUniqueUrls\Resolvers;

use Throwable;
use Vlados\LaravelUniqueUrls\Contracts\ControllerResolver;

class DefaultControllerResolver implements ControllerResolver
{
    /**
     * Resolve a controller by FQCN or Livewire component name.
     *
     * Resolution order:
     * 1. PHP class name (class_exists) — backward compatible
     * 2. Livewire component name — if Livewire is installed
     */
    public function resolve(string $controller): ?object
    {
        if (class_exists($controller)) {
            return app($controller);
        }

        if (app()->bound('livewire')) {
            try {
                return app('livewire')->new($controller);
            } catch (Throwable) {
                return null;
            }
        }

        return null;
    }
}
