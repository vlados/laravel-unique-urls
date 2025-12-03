<?php

declare(strict_types=1);

namespace Vlados\LaravelUniqueUrls\Exceptions;

use Exception;
use Illuminate\Database\Eloquent\Model;

class EmptySlugException extends Exception
{
    public static function forModel(Model $model): self
    {
        $modelClass = get_class($model);
        $modelId = $model->getKey();

        return new self(
            "Cannot generate URL: empty slug for {$modelClass} (ID: {$modelId}). " .
            "Check the urlStrategy() method returns a non-empty string."
        );
    }

    public static function afterTrimming(string $originalSlug, Model $model): self
    {
        $modelClass = get_class($model);
        $modelId = $model->getKey();

        return new self(
            "Cannot generate URL: slug is empty after trimming slashes for {$modelClass} (ID: {$modelId}). " .
            "Original slug: '{$originalSlug}'. " .
            "Ensure urlStrategy() doesn't return only slashes."
        );
    }
}
