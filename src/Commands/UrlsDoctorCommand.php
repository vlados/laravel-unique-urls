<?php

declare(strict_types=1);

namespace Vlados\LaravelUniqueUrls\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use ReflectionMethod;
use Vlados\LaravelUniqueUrls\Contracts\ControllerResolver;
use Vlados\LaravelUniqueUrls\HasUniqueUrls;
use Vlados\LaravelUniqueUrls\Services\ModelDiscoveryService;

class UrlsDoctorCommand extends Command
{
    public $signature = 'urls:doctor
        {--model= : Specify only a model for which to execute the command}
        {--strict : Exit with a failure code when no models were found to check}
    ';

    public $description = 'Check the URL configuration of every model using the HasUniqueUrls trait';

    private $errors = [];

    private int $checked = 0;

    private bool $scannedAllPaths = false;

    /**
     * @throws \Throwable
     */
    public function handle(): int
    {
        if ($model = $this->option('model')) {
            try {
                $modelClass = app(ModelDiscoveryService::class)->qualify((string) $model);
            } catch (InvalidArgumentException $e) {
                $this->error($e->getMessage());

                return self::FAILURE;
            }

            if (! class_exists($modelClass)) {
                $this->error("Model class {$modelClass} not found");

                return self::FAILURE;
            }

            $this->checkModel($modelClass);
        } else {
            $this->scannedAllPaths = true;

            $this->getModels()->each(function ($model): void {
                $this->checkModel((string) $model);
            });
        }

        return $this->outputErrors();
    }

    public function getModels(): Collection
    {
        $models = app(ModelDiscoveryService::class)->models()
            ->filter(static function ($class) {
                return method_exists($class, 'urls') && in_array(HasUniqueUrls::class, class_uses_recursive($class));
            });

        return $models->values();
    }

    /**
     * Run every check for one model. A model that cannot be instantiated or
     * blows up mid-check is recorded as an error rather than aborting the run:
     * one broken model must not cost the coverage of all the others.
     */
    private function checkModel(string $modelClass): void
    {
        $this->checked++;

        try {
            $model = app($modelClass);

            if (! $model instanceof Model) {
                $this->errors[$modelClass][] = 'The class is not an Eloquent model';

                return;
            }

            $this->check($model);
        } catch (\Throwable $e) {
            $this->errors[$modelClass][] = 'Checking this model failed: ' . $e->getMessage();
        }
    }

    private function check(Model $model): void
    {
        $this->checkConflictingColumns($model);
        $this->checkParams($model);
        $this->checkUrlHandler($model);
        $this->checkUrlStrategy($model);
    }

    private function checkConflictingColumns(Model $model): void
    {
        $modelName = $model::class;
        $conflictingColumns = ['url', 'urls'];

        foreach ($conflictingColumns as $column) {
            try {
                if ($model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), $column)) {
                    $this->errors[$modelName][] = "Model has a conflicting column '{$column}'. " .
                        "The HasUniqueUrls trait uses 'urls' as a relationship name and provides 'relative_url' and 'absolute_url' attributes. " .
                        "Please rename the '{$column}' column to avoid conflicts.";
                }
            } catch (\Exception $e) {
                // Skip if schema check fails
            }
        }
    }

    /**
     * @throws \ReflectionException
     */
    private function checkParams(Model $model): void
    {
        $modelName = $model::class;
        $modelReflection = new ReflectionMethod($model, 'urlStrategy');
        $traitReflection = new ReflectionMethod(HasUniqueUrls::class, 'urlStrategy');

        $eventParameters = $modelReflection->getParameters();
        $traitParameters = $traitReflection->getParameters();
        $parametersMatch = count($eventParameters) === count($traitParameters);

        if ($parametersMatch) {
            foreach ($eventParameters as $index => $eventParameter) {
                if ($eventParameter->getName() !== $traitParameters[$index]->getName()) {
                    $parametersMatch = false;

                    break;
                }
            }
        }

        if (! $parametersMatch) {
            $this->errors[$modelName][] = "The urlStrategy method in the {$modelName} class does not have the same parameters as in the HasUniqueUrls trait.";
        }
    }

    /**
     * Validate the handler the same way a live request resolves it: through the
     * ControllerResolver (which also knows Livewire component names), then via
     * the dispatch chain of LaravelUniqueUrlsController — __invoke() for the
     * Livewire style, otherwise the declared method with show()/index() as
     * fallbacks.
     */
    private function checkUrlHandler(Model $model): void
    {
        if (! method_exists($model, 'urlHandler')) {
            return;
        }
        $modelName = $model::class;

        $urlHandlerResult = $model->urlHandler();

        if (! is_array($urlHandlerResult)) {
            $this->errors[$modelName][] = 'The urlHandler method is not returning an array';

            return;
        }
        if (! isset($urlHandlerResult['controller'], $urlHandlerResult['method'], $urlHandlerResult['arguments'])) {
            $this->errors[$modelName][] = 'The urlHandler method is not returning an array with the keys: controller, method and arguments';

            return;
        }

        $controller = $urlHandlerResult['controller'];

        if (! is_string($controller) || trim($controller) === '') {
            $this->errors[$modelName][] = 'The urlHandler controller must be a non-empty string';

            return;
        }

        try {
            $instance = app(ControllerResolver::class)->resolve($controller);
        } catch (\Throwable $e) {
            $this->errors[$modelName][] = "The controller {$controller} could not be instantiated: {$e->getMessage()}";

            return;
        }

        if ($instance === null) {
            $this->errors[$modelName][] = "The controller {$controller} could not be resolved. It is neither an existing class nor a resolvable Livewire component.";

            return;
        }

        $method = (string) $urlHandlerResult['method'];

        // Livewire style: an empty method means the component is invoked.
        if ($method === '' && method_exists($instance, '__invoke')) {
            return;
        }

        $candidates = array_values(array_filter([$method, 'show', 'index']));

        foreach ($candidates as $candidate) {
            if (method_exists($instance, $candidate)) {
                return;
            }
        }

        if ($method === '') {
            $this->errors[$modelName][] = "The controller {$controller} has an empty method and none of __invoke(), show() or index() to fall back to";

            return;
        }

        $this->errors[$modelName][] = "The controller {$controller} has none of the methods: " .
            implode('(), ', $candidates) . '()';
    }

    private function checkUrlStrategy(Model $model): void
    {
        if (! method_exists($model, 'urlStrategy')) {
            return;
        }
        $modelName = $model::class;
        $languages = config('unique-urls.languages', []);
        if (! $languages && count($languages) < 2) {
            return;
        }

        $urlStrategyResult = [];

        try {
            foreach ($languages as $locale => $language) {
                $urlStrategyResult[$language] = $model->urlStrategy($language, $locale);
            }
            if (count(array_unique($urlStrategyResult)) !== count($languages)) {
                $this->errors[$modelName][] = 'The urlStrategy method is not implementing different strategies for different languages';
            }
        } catch (\Throwable $e) {
            // An empty instance often cannot build a slug (missing relations,
            // null attributes); that is not what this check is about.
        }
    }

    private function outputErrors(): int
    {
        if ($this->checked === 0) {
            return $this->reportNothingChecked();
        }

        if (count($this->errors)) {
            foreach ($this->errors as $model => $errors) {
                $this->error("Errors for {$model}");
                foreach ($errors as $error) {
                    $this->info(' - ' . $error);
                }
            }

            $this->newLine();
            $this->line($this->scopeSummary());

            return self::FAILURE;
        }

        $this->comment('Everything is ok — ' . $this->scopeSummary());

        return self::SUCCESS;
    }

    /**
     * Never report health without saying what was looked at: an empty scan is
     * a configuration problem, not a clean bill of health.
     */
    private function reportNothingChecked(): int
    {
        $paths = app(ModelDiscoveryService::class)->paths();

        $this->warn('No models using the HasUniqueUrls trait were found — nothing was checked.');
        $this->warn('Scanned ' . count($paths) . ' ' . Str::plural('path', count($paths)) . ':');

        foreach ($paths as $source) {
            $namespace = $source['namespace'] === '' ? '<application namespace>' : $source['namespace'] . '\\';
            $this->warn("  - {$source['path']} → {$namespace}");
        }

        $this->warn('Add the directory to the unique-urls.model_paths config if your models live somewhere else.');

        return $this->option('strict') ? self::FAILURE : self::SUCCESS;
    }

    private function scopeSummary(): string
    {
        $summary = "checked {$this->checked} " . Str::plural('model', $this->checked);

        if (! $this->scannedAllPaths) {
            return $summary;
        }

        $paths = count(app(ModelDiscoveryService::class)->paths());

        return $summary . " in {$paths} " . Str::plural('path', $paths);
    }
}
