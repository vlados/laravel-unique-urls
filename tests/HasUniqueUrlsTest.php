<?php

use Vlados\LaravelUniqueUrls\Models\Url;
use Vlados\LaravelUniqueUrls\Tests\Models\ChildModel;
use Vlados\LaravelUniqueUrls\Tests\Models\TestModel;

//uses(RefreshDatabase::class);

beforeEach(function () {
    app()->setLocale('en');
});

test('Check if it creates correct url', closure: function () {
    $model = TestModel::create(['name' => 'this is a test']);
    expect($model->absolute_url)->toEqual(url(app()->getLocale().'/parent/this-is-a-test'));
});

test('Check if the url for BG language is correct', closure: function () {
    $model = TestModel::create(['name' => 'this is a test']);
    app()->setLocale('bg');
    expect($model->absolute_url)->toEqual(url('bg/parent/this-is-a-test'));
});


test('Check if suffix is added for equal 3 records', closure: function () {
    $model = TestModel::create(['name' => 'multiple records']);
    expect($model->id)->toEqual(1);
    expect($model->absolute_url)->toEqual(url(app()->getLocale().'/parent/multiple-records'));
    expect($model->relative_url)->toEqual(app()->getLocale().'/parent/multiple-records');

    $model = TestModel::create(['name' => 'multiple records']);
    expect($model->id)->toEqual(2);
    expect($model->absolute_url)->toEqual(url(app()->getLocale().'/parent/multiple-records_1'));
    expect($model->relative_url)->toEqual(app()->getLocale().'/parent/multiple-records_1');

    $model = TestModel::create(['name' => 'multiple records']);
    expect($model->id)->toEqual(3);
    expect($model->absolute_url)->toEqual(url(app()->getLocale().'/parent/multiple-records_2'));
    expect($model->relative_url)->toEqual(app()->getLocale().'/parent/multiple-records_2');
});


test('Generate urls after import', closure: function () {
    $generate = 10;
    for ($i = 0;$i < $generate; $i++) {
        $model = new TestModel();
        $model->disableGeneratingUrlsOnCreate();
        $model->name = \Pest\Faker\faker()->text(20).time();
        $model->save();
        expect($model->url)->toBeNull();
    }
    TestModel::all()->each(callback: function (TestModel $model) {
        $model->generateUrl();
        expect($model->relative_url)
            ->toEqual(app()->getLocale().'/parent/' . Str::slug($model->getAttribute('name')));
    });
});

$generate = 1;
test("Generate multiple parent ($generate) and child urls ($generate), total: ".($generate * $generate), function () use ($generate) {
    $generatedTotal = 0;
    for ($i = 0; $i < $generate; $i++) {
        $translations = [];
        foreach (config('unique-urls.languages') as $locale => $lang) {
            $translations[$lang] = \Pest\Faker\faker($locale)->company().$i;
        }
        $parentModel = TestModel::create([
            'name' => $translations,
        ]);
        for ($b = 0; $b < $generate; $b++) {
            $translations = [];
            foreach (config('unique-urls.languages') as $locale => $lang) {
                $translations[$lang] = \Pest\Faker\faker($locale)->company().$b;
            }
            $childModel = ChildModel::create([
                'parent_id' => $parentModel->id,
                'name' => $translations,
            ]);
            $generatedTotal++;
            foreach (config('unique-urls.languages') as $locale => $lang) {
                expect($childModel->getSlug($lang))
                    ->toEqual($parentModel->getSlug($lang)
                        . '/' .
                        Str::slug($childModel->getTranslation('name', $lang), '-', $locale));
            }
        }
    }
    expect($generatedTotal)->toEqual($generate * $generate);
});

test('Check if urls deleted after model deleted', function () {
    $model = TestModel::create(['name' => 'this is a test']);
    expect($model->absolute_url)->toEqual(url(app()->getLocale().'/parent/this-is-a-test'));
    $newName = \Pest\Faker\faker()->text;
    $model->name = $newName;
    $model->save();
    expect($model->absolute_url)->toEqual(url(app()->getLocale().'/parent/'.Str::slug($newName)));
    $model->load(['urls']);
//    dd($model->relative_url);
    $urls = $model->urls;
    $model->delete();
    $urls->each(function ($item) {
        expect(\Vlados\LaravelUniqueUrls\Models\Url::find($item->id))->toBeNull();
    });
});

test('Check if url is updated correctly', function () {
    $model = TestModel::create(['name' => 'this is a test']);
    expect($model->absolute_url)->toEqual(url(app()->getLocale().'/parent/this-is-a-test'));
    $newName = \Pest\Faker\faker()->text;
    $model->name = $newName;
    $model->save();
    expect($model->absolute_url)->toEqual(url(app()->getLocale().'/parent/'.Str::slug($newName)));
});

test('Check if urls are created when updating, if for some reason they are deleted', function () {
    $model = TestModel::create(['name' => 'this is a test']);
    $model->urls()->delete();
    $newName = \Pest\Faker\faker()->text;
    $model->name = $newName;
    $model->save();
    $model->load(['urls']);
    $model->urls->each(function ($item) {
        expect(\Vlados\LaravelUniqueUrls\Models\Url::find($item->id))->toBeInstanceOf(Url::class);
    });
});

//test('Check if redirect after update', closure: function () {
//    $model = TestModel::create(['name' => 'this is a test']);
//    expect($model->absolute_url)->toEqual(url('test-this-is-a-test'));
//    $model->update(['name' => 'this is a second test']);
//    expect($model->relative_url)->toEqual(url('test-this-is-a-second-test'));
//
//    $request = Request::create('test-this-is-a-second-test');
//
////    expect($response->getStatusCode())->toEqual(301);
//
//});
