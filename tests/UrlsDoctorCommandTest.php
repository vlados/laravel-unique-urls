<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Vlados\LaravelUniqueUrls\Commands\UrlsDoctorCommand;
use Vlados\LaravelUniqueUrls\HasUniqueUrls;
use Vlados\LaravelUniqueUrls\Tests\Fixtures\BrokenHandlerModel;
use Vlados\LaravelUniqueUrls\Tests\Fixtures\ErrorInStrategyModel;
use Vlados\LaravelUniqueUrls\Tests\Fixtures\ShowOnlyController;
use Vlados\LaravelUniqueUrls\Tests\Fixtures\UnusableController;
use Vlados\LaravelUniqueUrls\Tests\TestUrlHandler;

beforeEach(function () {
    app()->setLocale('en');
});

$moduleModel = 'Modules\TestModule\Models\ModuleModel';

/**
 * Run only the urlHandler check and return the errors it recorded.
 */
$urlHandlerErrors = function (Model $model): array {
    $command = new UrlsDoctorCommand();
    $reflection = new ReflectionClass($command);

    $errors = $reflection->getProperty('errors');
    $errors->setValue($command, []);

    $reflection->getMethod('checkUrlHandler')->invoke($command, $model);

    return $errors->getValue($command)[$model::class] ?? [];
};

/**
 * A model whose urlHandler returns the given controller/method pair.
 */
$modelWithHandler = function (string $controller, string $method): Model {
    return new class ($controller, $method) extends Model {
        use HasUniqueUrls;

        protected $table = 'test_models';

        public $timestamps = false;

        public function __construct(private string $handlerController = '', private string $handlerMethod = '')
        {
            parent::__construct();
        }

        public function urlHandler(): array
        {
            return [
                'controller' => $this->handlerController,
                'method' => $this->handlerMethod,
                'arguments' => [],
            ];
        }

        public function urlStrategy($language, $locale): string
        {
            return $language . '/' . Str::slug((string) $this->name, '-', $locale);
        }
    };
};

// ============================================
// --model option
// ============================================

test('47. urls:doctor --model accepts a fully qualified module class name', function () use ($moduleModel) {
    $exitCode = Artisan::call('urls:doctor', ['--model' => $moduleModel]);

    expect($exitCode)->toBe(0)
        ->and(Artisan::output())->toContain('Everything is ok');
});

test('48. urls:doctor --model still resolves a bare name against App\Models', function () {
    $exitCode = Artisan::call('urls:doctor', ['--model' => 'ThereIsNoSuchModel']);

    expect($exitCode)->toBe(1)
        ->and(Artisan::output())->toContain('App\Models\ThereIsNoSuchModel');
});

// ============================================
// Reporting what was actually checked
// ============================================

test('49. urls:doctor reports how much it checked instead of a bare ok', function () {
    Config::set('modules.paths.modules', $this->fixtureModulesPath());

    $exitCode = Artisan::call('urls:doctor');
    $output = Artisan::output();

    expect($exitCode)->toBe(0)
        ->and($output)->toContain('Everything is ok')
        ->and($output)->toContain('checked 2 models')
        ->and($output)->toMatch('/in \d+ paths/');
});

test('50. urls:doctor warns instead of claiming health when nothing was checked', function () {
    Config::set('unique-urls.model_paths', [__DIR__ . '/Fixtures/EmptyModelPath']);

    $exitCode = Artisan::call('urls:doctor');
    $output = Artisan::output();

    expect($exitCode)->toBe(0)
        ->and($output)->not->toContain('Everything is ok')
        ->and($output)->toContain('nothing was checked')
        ->and($output)->toContain('EmptyModelPath')
        ->and($output)->toContain('model_paths');
});

test('51. urls:doctor --strict fails when nothing was checked', function () {
    Config::set('unique-urls.model_paths', [__DIR__ . '/Fixtures/EmptyModelPath']);

    expect(Artisan::call('urls:doctor', ['--strict' => true]))->toBe(1);
});

// ============================================
// urlHandler validation via the ControllerResolver
// ============================================

test('52. urls:doctor accepts a Livewire component name as controller', function () use ($urlHandlerErrors, $modelWithHandler) {
    app()->bind('livewire', fn () => new class () {
        public function new(string $name): object
        {
            if ($name !== 'module-page') {
                throw new RuntimeException("Unable to find component: [{$name}]");
            }

            return new class () {
                public function __invoke(): string
                {
                    return 'rendered';
                }
            };
        }
    });

    // A pinned Livewire name is not a class, so class_exists() reported it as broken.
    expect($urlHandlerErrors($modelWithHandler('module-page', '')))->toBe([]);
});

test('53. urls:doctor reports a controller that cannot be resolved', function () use ($urlHandlerErrors, $modelWithHandler) {
    $errors = $urlHandlerErrors($modelWithHandler('App\Http\Controllers\NoSuchController', 'show'));

    expect($errors)->toHaveCount(1)
        ->and($errors[0])->toContain('could not be resolved');
});

test('54. urls:doctor accepts the show()/index() fallback of the request handler', function () use ($urlHandlerErrors, $modelWithHandler) {
    // LaravelUniqueUrlsController falls back to show() then index() when the
    // declared method is missing, so this setup works at runtime.
    expect($urlHandlerErrors($modelWithHandler(TestUrlHandler::class, 'view')))->toBe([])
        ->and($urlHandlerErrors($modelWithHandler(ShowOnlyController::class, 'view')))->toBe([]);
});

test('55. urls:doctor still reports a controller without any usable method', function () use ($urlHandlerErrors, $modelWithHandler) {
    $errors = $urlHandlerErrors($modelWithHandler(UnusableController::class, 'view'));

    expect($errors)->toHaveCount(1)
        ->and($errors[0])->toContain('none of the methods');
});

// ============================================
// One broken model must not end the run
// ============================================

test('59. A controller the container cannot build is an error, not a crash', function () use ($urlHandlerErrors, $modelWithHandler) {
    // Resolving instantiates the controller, which class_exists() never did.
    $errors = $urlHandlerErrors(new BrokenHandlerModel());

    expect($errors)->toHaveCount(1)
        ->and($errors[0])->toContain('could not be instantiated')
        // The checker survived, so the next model still gets checked.
        ->and($urlHandlerErrors($modelWithHandler(TestUrlHandler::class, 'view')))->toBe([]);

    $exitCode = Artisan::call('urls:doctor', ['--model' => BrokenHandlerModel::class]);

    expect($exitCode)->toBe(1)
        ->and(Artisan::output())->toContain('could not be instantiated');
});

test('60. An Error raised by urlStrategy does not abort the run', function () {
    // urlStrategy() on an empty instance often cannot build a slug; when that
    // failure is an Error rather than an Exception it used to escape the check.
    $exitCode = Artisan::call('urls:doctor', ['--model' => ErrorInStrategyModel::class]);

    expect($exitCode)->toBe(0)
        ->and(Artisan::output())->toContain('Everything is ok');
});
