<?php

namespace Vlados\LaravelUniqueUrls\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;
use Vlados\LaravelUniqueUrls\HasUniqueUrls;
use Vlados\LaravelUniqueUrls\Tests\TestUrlHandler;

/**
 * @property string $name
 * @property Collection $urls
 */
class TestModel extends Model
{
    use HasUniqueUrls;
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
