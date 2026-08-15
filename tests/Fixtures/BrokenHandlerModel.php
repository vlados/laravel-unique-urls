<?php

namespace Vlados\LaravelUniqueUrls\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Vlados\LaravelUniqueUrls\HasUniqueUrls;

/**
 * Points at a controller the container cannot build. Lives outside every
 * scanned directory, so it is only checked when asked for by name.
 *
 * @property string $name
 */
class BrokenHandlerModel extends Model
{
    use HasUniqueUrls;

    protected $table = 'test_models';

    protected $guarded = [];

    public $timestamps = false;

    public function urlStrategy($language, $locale): string
    {
        return $language . '/broken/' . Str::slug((string) $this->name, '-', $locale);
    }

    public function urlHandler(): array
    {
        return [
            'controller' => UnresolvableController::class,
            'method' => 'show',
            'arguments' => [],
        ];
    }
}
