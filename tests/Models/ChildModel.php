<?php

namespace Vlados\LaravelUniqueUrls\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;
use Vlados\LaravelUniqueUrls\HasUniqueUrlTrait;
use Vlados\LaravelUniqueUrls\Tests\TestUrlHandler;

/**
 * Vlados\LaravelUniqueUrls\Tests\Models\TestModel.
 * @property string $name
 */
class ChildModel extends Model
{
    use HasUniqueUrlTrait;
    use HasTranslations;

    protected $table = 'child_models';
    protected $guarded = [];
    public $timestamps = false;
    public $translatable = ['name'];

    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(TestModel::class);
    }

    public function urlStrategy($language, $locale): string
    {
        return $this->parent->getSlug($language).'/' . Str::slug($this->getTranslation('name', $language), '-', $locale);
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
