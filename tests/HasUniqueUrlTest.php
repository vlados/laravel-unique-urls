<?php

use Vlados\LaravelUniqueUrls\Tests\Models\TestModel;

it('will save a slug when saving a model', closure: function () {
    $model = TestModel::create(['name' => 'this is a test']);

    expect($model->slug)->toEqual('test-this-is-a-test');
});
