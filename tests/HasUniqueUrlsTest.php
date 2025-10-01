<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Vlados\LaravelUniqueUrls\LaravelUniqueUrlsController;
use Vlados\LaravelUniqueUrls\Models\Url;
use Vlados\LaravelUniqueUrls\Tests\Models\ChildModel;
use Vlados\LaravelUniqueUrls\Tests\Models\TestModel;

beforeEach(function () {
    app()->setLocale('en');
    //    uses(RefreshDatabase::class);
});

afterAll(function () {
});

$generate = 10;

test('1. Check if it creates correct url', closure: function () {
    $model = TestModel::create(['name' => 'this is a test']);
    expect($model->absolute_url)->toEqual(url(app()->getLocale() . '/parent/this-is-a-test'));
});

test('2. Check if the url for BG language is correct', closure: function () {
    $model = TestModel::create(['name' => 'this is a test']);
    app()->setLocale('bg');
    expect($model->absolute_url)->toEqual(url('bg/parent/this-is-a-test'));
});


test('3. Check if suffix is added for equal 3 records', closure: function () {
    $model = TestModel::create(['name' => 'multiple records']);
    expect($model->id)->toEqual(1);
    expect($model->absolute_url)->toEqual(url(app()->getLocale() . '/parent/multiple-records'));
    expect($model->relative_url)->toEqual(app()->getLocale() . '/parent/multiple-records');

    $model = TestModel::create(['name' => 'multiple records']);
    expect($model->id)->toEqual(2);
    expect($model->absolute_url)->toEqual(url(app()->getLocale() . '/parent/multiple-records_1'));
    expect($model->relative_url)->toEqual(app()->getLocale() . '/parent/multiple-records_1');

    $model = TestModel::create(['name' => 'multiple records']);
    expect($model->id)->toEqual(3);
    expect($model->absolute_url)->toEqual(url(app()->getLocale() . '/parent/multiple-records_2'));
    expect($model->relative_url)->toEqual(app()->getLocale() . '/parent/multiple-records_2');
});


test('4. Generate urls after import', closure: function () {
    $generate = 10;
    for ($i = 0; $i < $generate; $i++) {
        $model = new TestModel();
        $model->disableGeneratingUrlsOnCreate();
        $model->name = fake()->text(20) . time();
        $model->save();
        expect($model->urls)->toBeEmpty();
    }
    TestModel::all()->each(callback: function (TestModel $model) {
        $model->generateUrl();
        expect($model->relative_url)
            ->toEqual(app()->getLocale() . '/parent/' . Str::slug($model->getAttribute('name')));
    });
});

test("5. Generate multiple parent ($generate) and child urls ($generate), total: " . ($generate * $generate), function () use ($generate) {
    $generatedTotal = 0;
    for ($i = 0; $i < $generate; $i++) {
        $translations = [];
        foreach (config('unique-urls.languages') as $locale => $lang) {
            $translations[$lang] = fake($locale)->company() . $i;
        }
        $parentModel = TestModel::create([
            'name' => $translations,
        ]);
        for ($b = 0; $b < $generate; $b++) {
            $translations = [];
            foreach (config('unique-urls.languages') as $locale => $lang) {
                $translations[$lang] = fake($locale)->company() . $b;
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

test('6. Check if urls deleted after model deleted', function () {
    $model = TestModel::create(['name' => 'this is a test']);
    expect($model->absolute_url)->toEqual(url(app()->getLocale() . '/parent/this-is-a-test'));
    $newName = fake()->text;
    $model->name = $newName;
    $model->save();
    expect($model->absolute_url)->toEqual(url(app()->getLocale() . '/parent/' . Str::slug($newName)));
    $model->load(['urls']);
    //    dd($model->relative_url);
    $urls = $model->urls;
    $model->delete();
    $urls->each(function ($item) {
        expect(\Vlados\LaravelUniqueUrls\Models\Url::find($item->id))->toBeNull();
    });
});

test('7. Check if url is updated correctly', function () {
    $model = TestModel::create(['name' => 'this is a test']);
    expect($model->absolute_url)->toEqual(url(app()->getLocale() . '/parent/this-is-a-test'));
    $newName = fake()->text;
    $model->name = $newName;
    $model->save();
    expect($model->absolute_url)->toEqual(url(app()->getLocale() . '/parent/' . Str::slug($newName)));
});

test('8. Check if urls are created when updating, if for some reason they are deleted', function () {
    $model = TestModel::create(['name' => 'this is a test']);
    $model->urls()->delete();
    $newName = fake()->text;
    $model->name = $newName;
    $model->save();
    $model->load(['urls']);
    $model->urls->each(function ($item) {
        expect(\Vlados\LaravelUniqueUrls\Models\Url::find($item->id))->toBeInstanceOf(Url::class);
    });
});

test('9. Check if after update the old url is redirected', function () {
    $model = TestModel::create(['name' => 'test']);
    $old_urls = $model->urls()->get();
    $model->name = 'test2';
    $model->save();
    $old_urls->each(function (Url $item) {
        $url = Url::where("slug", $item->slug)->where("language", $item->language)->first();
        expect($url->controller)->toEqual(LaravelUniqueUrlsController::class)
            ->and($url->method)->toEqual("handleRedirect");
    });
});

test('10. Check if visitor is redirected only ones, if the model have multiple redirects', function () {
    $model = TestModel::create(['name' => 'test']);
    $urls = [[$model->name => $model->relative_url]];
    for ($i = 1; $i < 10; $i++) {
        $model->name = 'test'.$i;
        $model->save();
        if ($i != 9) {
            $urls[] = [$model->name => $model->relative_url];
        }
    }

    $controller = resolve(LaravelUniqueUrlsController::class);
    foreach ($urls as $item) {
        $url = Url::where("slug", $item)->where("language", app()->getLocale())->first();
        $request = $controller->handleRequest($url, new Illuminate\Http\Request());
        expect($request->getStatusCode())->toEqual(301)
            ->and($request->getTargetUrl())->toEqual(url($model->relative_url));
    }
});


test('11. Check if it adds a suffix for same urls', function () use ($generate) {
    for ($i = 0; $i < $generate; $i++) {
        $model = TestModel::create(['name' => 'test']);
        expect($model->relative_url)->toEqual(app()->getLocale()."/parent/test".($i > 0 ? "_".$i : ""));
    }
});

test('12. Check if exception is thrown when model has conflicting url column', function () {
    // Create a table with 'url' column
    Schema::create('models_with_url_column', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name');
        $table->string('url');
    });

    // Create a model class that uses HasUniqueUrls trait
    $modelClass = new class extends \Illuminate\Database\Eloquent\Model {
        use \Vlados\LaravelUniqueUrls\HasUniqueUrls;

        protected $table = 'models_with_url_column';
        protected $guarded = [];
        public $timestamps = false;

        public function urlHandler(): array
        {
            return [
                'controller' => \Vlados\LaravelUniqueUrls\Tests\TestUrlHandler::class,
                'method' => 'view',
                'arguments' => [],
            ];
        }

        public function urlStrategy($language, $locale): string
        {
            return \Illuminate\Support\Str::slug($this->name);
        }
    };

    expect(fn() => $modelClass::create(['name' => 'test', 'url' => 'test-url']))
        ->toThrow(Exception::class, "has a conflicting column 'url'");

    Schema::dropIfExists('models_with_url_column');
});

test('13. Check if exception is thrown when model has conflicting urls column', function () {
    // Create a table with 'urls' column
    Schema::create('models_with_urls_column', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name');
        $table->string('urls');
    });

    // Create a model class that uses HasUniqueUrls trait
    $modelClass = new class extends \Illuminate\Database\Eloquent\Model {
        use \Vlados\LaravelUniqueUrls\HasUniqueUrls;

        protected $table = 'models_with_urls_column';
        protected $guarded = [];
        public $timestamps = false;

        public function urlHandler(): array
        {
            return [
                'controller' => \Vlados\LaravelUniqueUrls\Tests\TestUrlHandler::class,
                'method' => 'view',
                'arguments' => [],
            ];
        }

        public function urlStrategy($language, $locale): string
        {
            return \Illuminate\Support\Str::slug($this->name);
        }
    };

    expect(fn() => $modelClass::create(['name' => 'test', 'urls' => 'test-urls']))
        ->toThrow(Exception::class, "has a conflicting column 'urls'");

    Schema::dropIfExists('models_with_urls_column');
});
