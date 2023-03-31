<?php

namespace Vlados\LaravelUniqueUrls\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Spatie\ModelInfo\ModelFinder;

class RebuildUrlsCommand extends Command
{
    public $signature = 'urls:rebuild
        {--model= : Specify only a model for which to execute the command}
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
        if (! method_exists($model, "generateUrl")) {
            return false;
        }
        $records = app($model)->all();
        $generatedCount = 0;
        $records->each(function ($item) use (&$generatedCount) {
            $item->generateUrl();
            $generatedCount++;
//            $this->info("Generated URL: " . $item->relative_url);
        });
        if (app($model)->whereDoesntHave("urls")->count()) {
            throw new \Exception("Not all urls was generated");
        }
        $this->info("Generated $generatedCount urls for " . $model);
    }

    public function getModels(): Collection
    {
        $models = ModelFinder::all()
            ->filter(function ($class) {
                return method_exists($class, 'urls') && method_exists($class, 'generateUrl');
            });

        return $models->values();
    }
}
