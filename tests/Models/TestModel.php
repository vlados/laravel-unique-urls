<?php

namespace Vlados\LaravelUniqueUrls\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Vlados\LaravelUniqueUrls\HasUniqueUrlTrait;
use Vlados\LaravelUniqueUrls\Tests\TestUrlHandler;
use Vlados\LaravelUniqueUrls\UrlHandlerOptions;

/**
 * Vlados\LaravelUniqueUrls\Tests\Models\TestModel.
 * @property string $name
 */
class TestModel extends Model
{
    use HasUniqueUrlTrait;

    protected $table = 'test_models';
    protected $guarded = [];
    public $timestamps = false;

    public function urlStrategy(): string
    {
        return 'test-' . Str::slug($this->getAttribute('name'));
    }

    public function getUrlHandler()
    {
        return [
            'controller' => TestUrlHandler::class,
            'method' => 'view',
            'arguments' => [],
        ];
    }
}
