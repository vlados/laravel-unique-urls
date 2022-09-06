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
class TestModel extends Model
{
    use HasUniqueUrlTrait;
    use HasTranslations;

    protected $table = 'test_models';
    protected $guarded = [];
    public $timestamps = false;
    public $translatable = ['name'];

    public function urlStrategy($language, $locale): string
    {
        return $language.'/parent/' . Str::slug($this->getTranslation('name', $language), '-', $language);
    }

    public function asJson($value): bool|string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
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
