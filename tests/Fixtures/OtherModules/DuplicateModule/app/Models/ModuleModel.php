<?php

namespace Modules\DuplicateModule\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Vlados\LaravelUniqueUrls\HasUniqueUrls;
use Vlados\LaravelUniqueUrls\Tests\TestUrlHandler;

/**
 * Shares its short name with Modules\TestModule\Models\ModuleModel, so a bare
 * --model=ModuleModel cannot be resolved when both are discovered. Kept outside
 * tests/Fixtures/Modules so it only takes part in the tests that ask for it.
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
        return $language . '/duplicate/' . Str::slug((string) $this->name, '-', $locale);
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
