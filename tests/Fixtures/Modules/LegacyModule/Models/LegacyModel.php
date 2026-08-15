<?php

namespace Modules\LegacyModule\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Vlados\LaravelUniqueUrls\HasUniqueUrls;
use Vlados\LaravelUniqueUrls\Tests\TestUrlHandler;

/**
 * A model living in a module that follows the pre-v10 layout
 * (Modules/{Module}/Models), where the PSR-4 root is the module directory
 * itself rather than an app/ subdirectory.
 *
 * @property string $name
 */
class LegacyModel extends Model
{
    use HasUniqueUrls;

    protected $table = 'legacy_models';

    protected $guarded = [];

    public $timestamps = false;

    public function urlStrategy($language, $locale): string
    {
        return $language . '/legacy/' . Str::slug((string) $this->name, '-', $locale);
    }

    public function urlHandler(): array
    {
        return [
            'controller' => TestUrlHandler::class,
            'method' => 'view',
            'arguments' => [],
        ];
    }
}
