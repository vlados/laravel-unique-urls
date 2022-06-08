<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Vlados\LaravelUniqueUrls\Tests\Models\TestModel;

//uses(RefreshDatabase::class);

beforeEach(function() {
    app()->setLocale('en');
});

test('Check if it creates correct url', closure: function () {
    $model = TestModel::create(['name' => 'this is a test']);
    expect($model->getUrl())->toEqual(url('test-this-is-a-test'));
});

test('Check if the url for BG language is correct', closure: function () {
    $model = TestModel::create(['name' => 'this is a test']);
    app()->setLocale('bg');
    expect($model->getUrl())->toEqual(url('bg/test-this-is-a-test'));
});


test('Check if suffix is added for equal 3 records', closure: function () {
    $model = TestModel::create(['name' => 'multiple records']);
    expect($model->id)->toEqual(1);
    expect($model->getUrl())->toEqual(url('test-multiple-records'));
    expect($model->getUrl(false))->toEqual('test-multiple-records');

    $model = TestModel::create(['name' => 'multiple records']);
    expect($model->id)->toEqual(2);
    expect($model->getUrl())->toEqual(url('test-multiple-records_1'));
    expect($model->getUrl(false))->toEqual('test-multiple-records_1');

    $model = TestModel::create(['name' => 'multiple records']);
    expect($model->id)->toEqual(3);
    expect($model->getUrl())->toEqual(url('test-multiple-records_2'));
    expect($model->getUrl(false))->toEqual('test-multiple-records_2');
});


test('Generate urls after import', closure: function () {
    $generate = 10;
    for ($i = 0;$i < $generate; $i++) {
        $model = new TestModel();
        $model->setAutoGenerateUrls(false);
        $model->name = \Pest\Faker\faker()->text(20).time();
        $model->save();
        expect($model->url)->toBeNull();
    }
    TestModel::all()->each(callback: function (TestModel $model) {
        $model->generateUrl();
        expect($model->getUrl(false))->toEqual('test-' . Str::slug($model->getAttribute('name')));
    });
});

//test('Check if redirect after update', closure: function () {
//    $model = TestModel::create(['name' => 'this is a test']);
//    expect($model->getUrl())->toEqual(url('test-this-is-a-test'));
//    $model->update(['name' => 'this is a second test']);
//    expect($model->getUrl())->toEqual(url('test-this-is-a-second-test'));
//
//    $request = Request::create('test-this-is-a-second-test');
//
////    expect($response->getStatusCode())->toEqual(301);
//
//});
