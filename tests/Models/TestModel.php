<?php

namespace Vlados\LaravelUniqueUrls\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Vlados\LaravelUniqueUrls\HasUniqueUrlTrait;

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
}
