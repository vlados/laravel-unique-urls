<?php

namespace Vlados\LaravelUniqueUrls\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Spatie\ModelInfo\ModelFinder;
use Vlados\LaravelUniqueUrls\Models\Url;

class UrlsGenerateCommand extends Command
{
    public $signature = 'urls:generate
        {--model= : Specify only a model for which to execute the command}
        {--only-missing : Skip existing urls}
        {--fresh : Truncate table urls and generate fresh for every model}
    ';

    public $description = 'Generate unique urls';

    /**
     * @throws \Throwable
     */
    public function handle(): int
    {
        if ($this->option('fresh')) {
            $this->deleteUrls();
        }
        if ($model = $this->option('model')) {
            $this->generateUrls('\\App\\Models\\' . $model);
        } else {
            $this->getModels()->each(function ($model) {
                $this->generateUrls($model);
            });
        }

        $this->comment('All done');

        return self::SUCCESS;
    }

    public function generateUrls($model): void
    {
        if (! method_exists($model, "generateUrl")) {
            return;
        }
        $records = app($model)->all();
        $generatedCount = 0;
        $records->each(function ($item) use (&$generatedCount) {
            $item->generateUrl();
            $generatedCount++;
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

    private function deleteUrls(): void
    {
        if ($model = $this->option('model')) {
            if ($this->output->isVerbose()) {
                $this->info("Deleting all urls for model: " . $model);
            }
            Url::whereHasMorph('related', ['App\\Models\\' . $model])->delete();
        } else {
            if ($this->output->isVerbose()) {
                $this->info("Clearing urls table");
            }
            Url::truncate();
        }
    }
}
