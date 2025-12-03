<?php

declare(strict_types=1);

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
        {--chunk-size=500 : Number of records to process in a chunk}
        {--force : Force generation even if isAutoGenerateUrls() returns false}
    ';

    public $description = 'Generate unique urls';

    protected int $totalGenerated = 0;
    protected int $totalSkipped = 0;
    protected int $totalFailed = 0;
    protected float $startTime;

    /**
     * @throws \Throwable
     */
    public function handle(): int
    {
        $this->startTime = microtime(true);
        $this->totalGenerated = 0;
        $this->totalSkipped = 0;
        $this->totalFailed = 0;

        if ($this->option('fresh')) {
            $this->deleteUrls();
        }

        $this->processModels();

        $this->displaySummary();

        return self::SUCCESS;
    }

    public function generateUrls(string $modelClass): void
    {
        if (! class_exists($modelClass)) {
            $this->error("Model class {$modelClass} not found");

            return;
        }

        $model = app($modelClass);

        if (! method_exists($model, 'generateUrl')) {
            $this->warn("Model {$modelClass} does not have generateUrl() method");

            return;
        }

        // Check if auto-generation is enabled
        if (method_exists($model, 'isAutoGenerateUrls') && ! $model->isAutoGenerateUrls()) {
            if (! $this->option('force')) {
                $this->warn("⚠ {$modelClass} has isAutoGenerateUrls() = false");
                $this->warn("  URLs will not be generated automatically.");
                $this->warn("  Use --force flag to generate anyway, or enable in model.");
                $this->newLine();

                return;
            }

            $this->warn("Forcing URL generation for {$modelClass} (isAutoGenerateUrls = false)");
        }

        $this->info("Generating URLs for {$modelClass}...");
        $this->newLine();

        $total = $model->count();
        $withUrls = $model->has('urls')->count();
        $withoutUrls = $total - $withUrls;

        // Display stats
        $this->line("├─ Found: <fg=yellow>{$total}</> models");
        $this->line("├─ With URLs: <fg=green>{$withUrls}</>");
        $this->line("├─ Without URLs: <fg=cyan>{$withoutUrls}</>");

        if ($withoutUrls === 0 && $this->option('only-missing')) {
            $this->line("└─ <fg=green>All models already have URLs, skipping...</>");
            $this->newLine();

            return;
        }

        $generated = 0;
        $skipped = 0;
        $failed = 0;

        $chunkSize = (int) ($this->option('chunk-size') ?? 500);

        // Create progress bar
        $progressTotal = $this->option('only-missing') ? $withoutUrls : $total;
        $bar = $this->output->createProgressBar($progressTotal);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | %message%');
        $bar->setMessage('Starting...');

        $query = $this->option('only-missing')
            ? $model->whereDoesntHave('urls')
            : $model->query();

        $query->chunkById($chunkSize, function ($records) use (&$generated, &$skipped, &$failed, $bar) {
            foreach ($records as $record) {
                try {
                    // Check if URL exists (for non-only-missing mode)
                    if (! $this->option('only-missing') && $record->urls()->exists()) {
                        $skipped++;
                        $bar->setMessage('Skipping...');
                    } else {
                        $record->generateUrl();
                        $generated++;
                        $bar->setMessage('Generating...');
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    $bar->setMessage('Failed: ' . $e->getMessage());

                    if ($this->output->isVerbose()) {
                        $this->error("  Failed to generate URL for {$record->getKey()}: {$e->getMessage()}");
                    }
                }

                $bar->advance();
            }

            // Memory management
            gc_collect_cycles();
        });

        $bar->finish();
        $this->newLine(2);

        // Display results
        if ($generated > 0) {
            $this->line("├─ Generated: <fg=green>{$generated}</> URLs");
        }
        if ($skipped > 0) {
            $this->line("├─ Skipped: <fg=yellow>{$skipped}</> (already exist)");
        }
        if ($failed > 0) {
            $this->line("├─ Failed: <fg=red>{$failed}</> (check logs)");
        }
        $this->line("└─ <fg=green>✓ Completed</>");
        $this->newLine();

        $this->totalGenerated += $generated;
        $this->totalSkipped += $skipped;
        $this->totalFailed += $failed;
    }

    public function getModels(): Collection
    {
        $models = ModelFinder::all()
            ->filter(static function ($class) {
                return method_exists($class, 'urls') && method_exists($class, 'generateUrl');
            });

        return $models->values();
    }

    private function processModels(): void
    {
        if ($model = $this->option('model')) {
            // Handle both full class name and short name
            $modelClass = str_contains($model, '\\')
                ? $model
                : '\\App\\Models\\' . $model;

            $this->generateUrls($modelClass);
        } else {
            $models = $this->getModels();

            if ($models->isEmpty()) {
                $this->warn('No models found with generateUrl() method');

                return;
            }

            $this->info("Found {$models->count()} models with URL generation support");
            $this->newLine();

            $models->each(function ($modelClass): void {
                $this->generateUrls($modelClass);
            });
        }
    }

    private function deleteUrls(): void
    {
        if ($model = $this->option('model')) {
            $modelClass = str_contains($model, '\\')
                ? $model
                : 'App\\Models\\' . $model;

            if ($this->output->isVerbose()) {
                $this->info('Deleting all urls for model: ' . $model);
            }

            $count = Url::whereHasMorph('related', [$modelClass])->delete();
            $this->info("Deleted {$count} URLs for {$modelClass}");
        } else {
            if ($this->output->isVerbose()) {
                $this->info('Clearing urls table');
            }

            $count = Url::count();
            Url::truncate();
            $this->info("Deleted {$count} URLs");
        }

        $this->newLine();
    }

    private function displaySummary(): void
    {
        $duration = round(microtime(true) - $this->startTime, 2);

        $this->newLine();
        $this->info('═══════════════════════════════════════');
        $this->info('           Summary');
        $this->info('═══════════════════════════════════════');

        if ($this->totalGenerated > 0) {
            $this->line("  Generated: <fg=green>{$this->totalGenerated}</> URLs");
        }
        if ($this->totalSkipped > 0) {
            $this->line("  Skipped: <fg=yellow>{$this->totalSkipped}</> URLs");
        }
        if ($this->totalFailed > 0) {
            $this->line("  Failed: <fg=red>{$this->totalFailed}</> URLs");
        }

        $this->line("  Duration: <fg=cyan>{$duration}s</>");

        $this->info('═══════════════════════════════════════');
        $this->newLine();

        if ($this->totalGenerated === 0 && $this->totalSkipped === 0 && $this->totalFailed === 0) {
            $this->comment('All done (no URLs generated)');
        } else {
            $this->comment('All done ✓');
        }
    }
}
