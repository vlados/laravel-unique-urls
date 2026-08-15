<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Modules\TestModule\Models\ModuleModel;
use Spatie\ModelInfo\ModelFinder;
use Vlados\LaravelUniqueUrls\Commands\UrlsGenerateCommand;
use Vlados\LaravelUniqueUrls\Models\Url;
use Vlados\LaravelUniqueUrls\Services\ModelDiscoveryService;
use Vlados\LaravelUniqueUrls\Tests\Models\TestModel;

beforeEach(function () {
    app()->setLocale('en');
});

$moduleModel = 'Modules\TestModule\Models\ModuleModel';
$legacyModel = 'Modules\LegacyModule\Models\LegacyModel';

// ============================================
// Model discovery outside app/
// ============================================

test('37. ModelFinder on its own is blind to models outside app/', function () use ($moduleModel) {
    // Guards the regression: the default scan is app_path() only, which is why
    // module models silently disappeared from urls:generate and urls:doctor.
    expect(ModelFinder::all()->all())->not->toContain($moduleModel);
});

test('38. Modules are discovered for both the app/ and the legacy layout', function () use ($moduleModel, $legacyModel) {
    Config::set('modules.paths.modules', $this->fixtureModulesPath());

    $models = app(ModelDiscoveryService::class)->models()->all();

    expect($models)->toContain($moduleModel)
        ->and($models)->toContain($legacyModel);
});

test('39. A module is scanned with its PSR-4 root, not the module directory', function () {
    Config::set('modules.paths.modules', $this->fixtureModulesPath());

    $paths = collect(app(ModelDiscoveryService::class)->paths());

    $module = $paths->firstWhere('namespace', 'Modules\TestModule');
    $legacy = $paths->firstWhere('namespace', 'Modules\LegacyModule');

    // Modules/TestModule/app is the PSR-4 root of Modules\TestModule\ — using
    // the module directory instead would produce Modules\TestModule\app\Models\…
    expect($module)->not->toBeNull()
        ->and($module['path'])->toEndWith(implode(DIRECTORY_SEPARATOR, ['Modules', 'TestModule', 'app']))
        ->and($module['base_path'])->toBe($module['path']);

    // The legacy layout has no app/ directory: only Models/ is scanned, but the
    // namespace is still rooted at the module directory.
    expect($legacy)->not->toBeNull()
        ->and($legacy['path'])->toEndWith(implode(DIRECTORY_SEPARATOR, ['Modules', 'LegacyModule', 'Models']))
        ->and($legacy['base_path'])->toEndWith(implode(DIRECTORY_SEPARATOR, ['Modules', 'LegacyModule']))
        ->and($legacy['base_path'])->not->toBe($legacy['path']);
});

test('40. model_paths takes over and guesses the namespace from composer PSR-4', function () use ($moduleModel, $legacyModel) {
    Config::set('modules.paths.modules', $this->fixtureModulesPath());
    Config::set('unique-urls.model_paths', [
        __DIR__ . '/Fixtures/Modules/TestModule/app',
    ]);

    $service = app(ModelDiscoveryService::class);
    $paths = $service->paths();
    $models = $service->models()->all();

    expect($paths)->toHaveCount(1)
        ->and($paths[0]['namespace'])->toBe('Modules\TestModule')
        ->and($models)->toContain($moduleModel)
        ->and($models)->not->toContain($legacyModel);
});

test('41. model_paths accepts an explicit namespace => directory mapping', function () use ($moduleModel) {
    Config::set('unique-urls.model_paths', [
        'Modules\TestModule' => __DIR__ . '/Fixtures/Modules/TestModule/app',
    ]);

    $service = app(ModelDiscoveryService::class);

    expect($service->paths()[0]['namespace'])->toBe('Modules\TestModule')
        ->and($service->models()->all())->toContain($moduleModel);
});

test('42. Directories that do not exist are skipped instead of throwing', function () {
    Config::set('unique-urls.model_paths', [
        __DIR__ . '/Fixtures/there-is-no-such-directory',
    ]);

    $service = app(ModelDiscoveryService::class);

    expect($service->paths())->toBe([])
        ->and($service->models()->all())->toBe([]);
});

test('43. A directory without models yields no models and no error', function () {
    Config::set('unique-urls.model_paths', [
        __DIR__ . '/Fixtures/EmptyModelPath',
    ]);

    $service = app(ModelDiscoveryService::class);

    expect($service->paths())->toHaveCount(1)
        ->and($service->models()->all())->toBe([]);
});

// ============================================
// urls:generate
// ============================================

test('44. urls:generate finds module models', function () use ($moduleModel) {
    Config::set('modules.paths.modules', $this->fixtureModulesPath());

    expect((new UrlsGenerateCommand())->getModels()->all())->toContain($moduleModel);
});

test('45. urls:generate --model accepts a fully qualified module class name', function () use ($moduleModel) {
    $model = new ModuleModel();
    $model->disableGeneratingUrlsOnCreate();
    $model->name = 'module product';
    $model->save();

    expect($model->urls()->count())->toBe(0);

    $exitCode = Artisan::call('urls:generate', ['--model' => $moduleModel]);

    expect($exitCode)->toBe(0)
        ->and($model->urls()->count())->toBeGreaterThan(0);
});

test('46. urls:generate --fresh --model only deletes urls of the given model', function () {
    $module = ModuleModel::create(['name' => 'module product']);
    $other = TestModel::create(['name' => 'app product']);

    $moduleUrlIds = $module->urls()->orderBy('id')->pluck('id')->all();
    $otherUrlIds = $other->urls()->orderBy('id')->pluck('id')->all();

    expect($moduleUrlIds)->not->toBeEmpty()
        ->and($otherUrlIds)->not->toBeEmpty();

    // A leading backslash is normalised away before the class is used.
    Artisan::call('urls:generate', [
        '--model' => '\\Modules\\TestModule\\Models\\ModuleModel',
        '--fresh' => true,
    ]);

    expect($module->urls()->orderBy('id')->pluck('id')->all())->not->toEqual($moduleUrlIds)
        ->and($other->urls()->orderBy('id')->pluck('id')->all())->toEqual($otherUrlIds)
        ->and(Url::where('related_type', TestModel::class)->count())->toBe(count($otherUrlIds));
});

// ============================================
// Short --model names
// ============================================

test('56. A short model name falls back to the discovered models', function () use ($moduleModel) {
    Config::set('modules.paths.modules', $this->fixtureModulesPath());

    // App\Models\ModuleModel does not exist — the model lives in a module now.
    expect(app(ModelDiscoveryService::class)->qualify('ModuleModel'))->toBe($moduleModel);
});

test('57. An ambiguous short model name is reported instead of guessed', function () {
    Config::set('unique-urls.model_paths', [
        __DIR__ . '/Fixtures/Modules/TestModule/app',
        __DIR__ . '/Fixtures/OtherModules/DuplicateModule/app',
    ]);

    expect(fn () => app(ModelDiscoveryService::class)->qualify('ModuleModel'))
        ->toThrow(InvalidArgumentException::class, 'matches more than one class');

    $exitCode = Artisan::call('urls:generate', ['--model' => 'ModuleModel']);

    expect($exitCode)->toBe(1)
        ->and(Artisan::output())->toContain('matches more than one class');
});

test('58. An unknown short model name still reports the App\Models class', function () {
    $exitCode = Artisan::call('urls:generate', ['--model' => 'ThereIsNoSuchModel']);
    $output = Artisan::output();

    expect($exitCode)->toBe(0)
        ->and($output)->toContain('App\Models\ThereIsNoSuchModel')
        ->and($output)->toContain('not found');
});
