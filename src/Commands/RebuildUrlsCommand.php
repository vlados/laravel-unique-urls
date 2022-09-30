<?php

namespace Vlados\LaravelUniqueUrls\Commands;

use Illuminate\Console\Command;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class RebuildUrlsCommand extends Command
{
    public $signature = 'urls:rebuild
        {--model= : Rebuild only that model}
    ';

    public $description = 'Rebuild unique urls';

    /**
     * @throws \Throwable
     */
    public function handle(): int
    {
        if ($model = $this->option('model')) {
            $this->regenerate('\\App\\Models\\' . $model);
        } else {
            $this->getModels()->each(function ($model) {
                $this->regenerate($model);
            });
        }

        $this->comment('All done');

        return self::SUCCESS;
    }

    public function regenerate($model)
    {
        $records = $model::all();
        $generatedCount = 0;
        $records->each(function (Model $item) use (&$generatedCount) {
            $item->generateUrl();
            $generatedCount++;
//            $this->info("Generated URL: " . $item->relative_url);
        });
        if ($model::whereDoesntHave("urls")->count()) {
            throw new \Exception("Not all urls was generated");
        }
        $this->info("Generated $generatedCount urls for ".$model);
    }

    public function getModels(): Collection
    {
        $models = collect(File::allFiles(app_path()))
            ->map(function ($item) {
                $path = $item->getRelativePathName();
                $class = sprintf(
                    '\%s%s',
                    Container::getInstance()->getNamespace(),
                    strtr(substr($path, 0, strrpos($path, '.')), '/', '\\')
                );

                return $class;
            })
            ->filter(function ($class) {
                $valid = false;

                if (class_exists($class)) {
                    $reflection = new \ReflectionClass($class);
                    $valid = $reflection->isSubclassOf(Model::class) &&
                        ! $reflection->isAbstract();
                }

                return $valid;
            })->filter(function ($class) {
                return method_exists($class, 'urls');
            });

        return $models->values();
    }
}
