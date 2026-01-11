<?php

declare(strict_types=1);

namespace Vlados\LaravelUniqueUrls\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use ReflectionMethod;
use Spatie\ModelInfo\ModelFinder;
use Vlados\LaravelUniqueUrls\HasUniqueUrls;

class UrlsDoctorCommand extends Command
{
    public $signature = 'urls:doctor
        {--model= : Specify only a model for which to execute the command}
    ';

    public $description = 'Generate unique urls';
    private $errors = [];

    /**
     * @throws \Throwable
     */
    public function handle(): int
    {
        if ($model = $this->option('model')) {
            $this->check(app('\\App\\Models\\' . $model));
        } else {
            $this->getModels()->each(function ($model): void {
                $this->check(app($model));
            });
        }

        return $this->outputErrors();
    }

    public function getModels(): Collection
    {
        $models = ModelFinder::all()
            ->filter(static function ($class) {
                return method_exists($class, 'urls') && in_array(HasUniqueUrls::class, class_uses($class));
            });

        return $models->values();
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
            $this->errors[$modelName][] = "The urlStrategy method in the ${modelName} class does not have the same parameters as in the HasUniqueUrls trait.";
        }
    }

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

        if (! class_exists($urlHandlerResult['controller'])) {
            $this->errors[$modelName][] = "The class {$urlHandlerResult['controller']} does not exist";
        }

        $method = $urlHandlerResult['method'] ?: '__invoke';
        if (! method_exists($urlHandlerResult['controller'], $method)) {
            $this->errors[$modelName][] = "The method {$urlHandlerResult['controller']}:{$method} does not exist";
        }
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
        } catch (\Exception $e) {
            // do nothing
        }
    }

    private function outputErrors(): int
    {
        if (count($this->errors)) {
            foreach ($this->errors as $model => $errors) {
                $this->error("Errors for {$model}");
                foreach ($errors as $error) {
                    $this->info(' - ' . $error);
                }
            }

            return self::FAILURE;
        }

        $this->comment('Everything is ok');

        return self::SUCCESS;
    }
}
