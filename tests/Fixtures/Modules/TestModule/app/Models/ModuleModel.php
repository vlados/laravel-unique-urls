<?php

namespace Modules\TestModule\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Vlados\LaravelUniqueUrls\HasUniqueUrls;
use Vlados\LaravelUniqueUrls\Tests\TestUrlHandler;

/**
 * A model living in a module that follows the nwidart/laravel-modules layout
 * (Modules/{Module}/app/Models), where the PSR-4 root is the module's app/
 * directory. Used to prove the commands see beyond app_path().
 *
 * @property string $name
 */
class ModuleModel extends Model
{
    use HasUniqueUrls;

    protected $table = 'module_models';

    protected $guarded = [];

    public $timestamps = false;

    public function urlStrategy($language, $locale): string
    {
        return $language . '/module/' . Str::slug((string) $this->name, '-', $locale);
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
