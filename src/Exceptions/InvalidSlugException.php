<?php

declare(strict_types=1);

namespace Vlados\LaravelUniqueUrls\Exceptions;

use Exception;
use Illuminate\Database\Eloquent\Model;

class InvalidSlugException extends Exception
{
    public static function hasLeadingSlash(string $slug, Model $model): self
    {
        $modelClass = get_class($model);
        $modelId = $model->getKey();

        return new self(
            "Invalid slug '{$slug}' for {$modelClass} (ID: {$modelId}): contains leading slash. " .
            "Slugs should not start with '/'. Check urlStrategy() method."
        );
    }

    public static function hasTrailingSlash(string $slug, Model $model): self
    {
        $modelClass = get_class($model);
        $modelId = $model->getKey();

        return new self(
            "Invalid slug '{$slug}' for {$modelClass} (ID: {$modelId}): contains trailing slash. " .
            "Slugs should not end with '/'. Check urlStrategy() method."
        );
    }

    public static function containsInvalidCharacters(string $slug, Model $model): self
    {
        $modelClass = get_class($model);
        $modelId = $model->getKey();

        return new self(
            "Invalid slug '{$slug}' for {$modelClass} (ID: {$modelId}): contains invalid characters. " .
            "Slugs should only contain lowercase letters, numbers, and hyphens."
        );
    }

    public static function isReserved(string $slug, Model $model): self
    {
        $modelClass = get_class($model);
        $modelId = $model->getKey();

        return new self(
            "Invalid slug '{$slug}' for {$modelClass} (ID: {$modelId}): slug is reserved. " .
            "Reserved slugs are defined in config/unique-urls.php. Choose a different slug."
        );
    }
}
