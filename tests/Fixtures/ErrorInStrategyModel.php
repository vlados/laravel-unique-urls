<?php

namespace Vlados\LaravelUniqueUrls\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Vlados\LaravelUniqueUrls\HasUniqueUrls;

/**
 * Its urlStrategy() raises an Error — not an Exception — on an empty instance,
 * the shape every model that walks a parent relation has. Lives outside every
 * scanned directory, so it is only checked when asked for by name.
 */
class ErrorInStrategyModel extends Model
{
    use HasUniqueUrls;

    protected $table = 'test_models';

    protected $guarded = [];

    public $timestamps = false;

    public function urlStrategy($language, $locale): string
    {
        $parent = null;

        // Error: Call to a member function getSlug() on null
        return $parent->getSlug($language);
    }

    public function urlHandler(): array
    {
        return [
            'controller' => ShowOnlyController::class,
            'method' => 'show',
            'arguments' => [],
        ];
    }
}
