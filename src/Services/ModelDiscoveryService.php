<?php

declare(strict_types=1);

namespace Vlados\LaravelUniqueUrls\Services;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use Spatie\ModelInfo\ModelFinder;

/**
 * Finds the Eloquent models the package commands should work on.
 *
 * Spatie's ModelFinder only scans app_path() by default, so models living in
 * a modular structure (Modules/Blog/app/Models, packages/..., src/Domain/...)
 * are invisible to urls:generate and urls:doctor. This service resolves the
 * list of directories to scan together with the PSR-4 root and namespace
 * prefix that turns a file path into a class name.
 *
 * @phpstan-type ModelSource array{path: string, base_path: string, namespace: string}
 */
class ModelDiscoveryService
{
    /**
     * All discovered model class names, from every configured path.
     *
     * @return Collection<int, class-string<\Illuminate\Database\Eloquent\Model>>
     */
    public function models(): Collection
    {
        return collect($this->paths())
            ->flatMap(static fn (array $source): array => ModelFinder::all(
                $source['path'],
                $source['base_path'],
                $source['namespace'],
            )->all())
            ->unique()
            ->values();
    }

    /**
     * The directories that are scanned for models.
     *
     * Each entry carries the directory to scan, the PSR-4 root directory the
     * namespace prefix is relative to, and that namespace prefix itself.
     *
     * @return array<int, ModelSource>
     */
    public function paths(): array
    {
        $configured = config('unique-urls.model_paths');

        $sources = is_array($configured) && $configured !== []
            ? $this->configuredSources($configured)
            : $this->defaultSources();

        return $this->unique($sources);
    }

    /**
     * Turn a --model option value into a fully qualified class name.
     *
     * A value containing a namespace separator is used as-is (only normalised).
     * A bare class name resolves to App\Models first, and falls back to the
     * discovered models, so a model that moved into a module still answers to
     * its short name.
     *
     * @throws InvalidArgumentException when a bare name matches several models
     */
    public function qualify(string $model): string
    {
        $model = ltrim(trim($model), '\\');

        if ($model === '' || str_contains($model, '\\')) {
            return $model;
        }

        $applicationModel = 'App\\Models\\' . $model;

        if (class_exists($applicationModel)) {
            return $applicationModel;
        }

        $matches = $this->models()
            ->filter(static fn (string $class): bool => class_basename($class) === $model)
            ->values();

        if ($matches->count() > 1) {
            throw new InvalidArgumentException(
                "The model name {$model} matches more than one class: " . $matches->implode(', ') .
                '. Pass the fully qualified class name.',
            );
        }

        return $matches->count() === 1 ? (string) $matches->first() : $applicationModel;
    }

    /**
     * app/ plus every module found under the modules directory.
     *
     * @return array<int, ModelSource>
     */
    private function defaultSources(): array
    {
        $sources = [];

        if ($appSource = $this->source(app_path())) {
            $sources[] = $appSource;
        }

        return array_merge($sources, $this->moduleSources());
    }

    /**
     * Modules discovered by convention: {modules directory}/{Module}/app,
     * falling back to the pre-v10 {Module}/Models and {Module}/Entities
     * layouts. The namespace follows nwidart/laravel-modules, whose config is
     * honoured when the package is installed.
     *
     * @return array<int, ModelSource>
     */
    private function moduleSources(): array
    {
        $root = config('modules.paths.modules') ?: base_path('Modules');

        if (! is_string($root) || ! is_dir($root)) {
            return [];
        }

        $rootNamespace = trim((string) config('modules.namespace', 'Modules'), '\\');

        $sources = [];

        foreach ($this->directoriesIn($root) as $moduleDirectory) {
            $namespace = $rootNamespace . '\\' . basename($moduleDirectory);

            if (is_dir($moduleDirectory . DIRECTORY_SEPARATOR . 'app')) {
                $sources[] = $this->source($moduleDirectory . DIRECTORY_SEPARATOR . 'app', $namespace);

                continue;
            }

            foreach (['Models', 'Entities'] as $legacyDirectory) {
                if (is_dir($moduleDirectory . DIRECTORY_SEPARATOR . $legacyDirectory)) {
                    $sources[] = $this->source(
                        $moduleDirectory . DIRECTORY_SEPARATOR . $legacyDirectory,
                        $namespace,
                        $moduleDirectory,
                    );
                }
            }
        }

        return array_values(array_filter($sources));
    }

    /**
     * @param array<array-key, mixed> $configured
     * @return array<int, ModelSource>
     */
    private function configuredSources(array $configured): array
    {
        $sources = [];

        foreach ($configured as $key => $value) {
            if (is_array($value)) {
                $path = (string) ($value['path'] ?? '');
                $sources[] = $this->source(
                    $path,
                    isset($value['namespace']) ? (string) $value['namespace'] : null,
                    isset($value['base_path']) ? (string) $value['base_path'] : null,
                );

                continue;
            }

            // 'App\Domain' => '/path/to/domain'
            $namespace = is_string($key) ? $key : null;

            $sources[] = $this->source((string) $value, $namespace);
        }

        return array_values(array_filter($sources));
    }

    /**
     * Build a scan descriptor, guessing the namespace from Composer's PSR-4
     * map when it was not given explicitly.
     *
     * @return ModelSource|null
     */
    private function source(string $path, ?string $namespace = null, ?string $basePath = null): ?array
    {
        $path = $this->realPath($path);

        if ($path === null) {
            return null;
        }

        if ($namespace !== null) {
            return [
                'path' => $path,
                'base_path' => $this->realPath($basePath ?? $path) ?? $path,
                'namespace' => trim($namespace, '\\'),
            ];
        }

        if ($guessed = $this->guessFromComposer($path)) {
            return ['path' => $path] + $guessed;
        }

        // ModelFinder's own default: paths relative to the project root, with
        // the App\ prefix swapped for the application namespace.
        return [
            'path' => $path,
            'base_path' => $this->realPath(base_path()) ?? base_path(),
            'namespace' => '',
        ];
    }

    /**
     * Find the PSR-4 prefix whose root directory contains the given path. The
     * deepest matching root wins, so Modules\Blog\ (Modules/Blog/app) beats a
     * shallower mapping of the same tree.
     *
     * @return array{base_path: string, namespace: string}|null
     */
    private function guessFromComposer(string $path): ?array
    {
        $match = null;

        foreach ($this->psr4Prefixes() as $prefix => $directories) {
            foreach ((array) $directories as $directory) {
                $directory = $this->realPath((string) $directory);

                if ($directory === null || ! $this->isWithin($path, $directory)) {
                    continue;
                }

                if ($match === null || strlen($directory) > strlen($match['base_path'])) {
                    $match = [
                        'base_path' => $directory,
                        'namespace' => trim((string) $prefix, '\\'),
                    ];
                }
            }
        }

        return $match;
    }

    /**
     * The PSR-4 map of the Composer autoloader currently registered, if any.
     *
     * @return array<string, array<int, string>|string>
     */
    private function psr4Prefixes(): array
    {
        foreach ((array) spl_autoload_functions() as $autoloader) {
            if (! is_array($autoloader)) {
                continue;
            }

            $loader = $autoloader[0];

            if (is_object($loader) && method_exists($loader, 'getPrefixesPsr4')) {
                /** @var array<string, array<int, string>|string> $prefixes */
                $prefixes = $loader->getPrefixesPsr4();

                return $prefixes;
            }
        }

        return [];
    }

    /**
     * @return array<int, string>
     */
    private function directoriesIn(string $path): array
    {
        $directories = glob(rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);

        return $directories === false ? [] : $directories;
    }

    private function isWithin(string $path, string $directory): bool
    {
        $directory = rtrim($directory, DIRECTORY_SEPARATOR);

        return $path === $directory
            || str_starts_with($path . DIRECTORY_SEPARATOR, $directory . DIRECTORY_SEPARATOR);
    }

    private function realPath(string $path): ?string
    {
        if ($path === '' || ! is_dir($path)) {
            return null;
        }

        $real = realpath($path);

        return $real === false ? null : $real;
    }

    /**
     * @param array<int, ModelSource> $sources
     * @return array<int, ModelSource>
     */
    private function unique(array $sources): array
    {
        $seen = [];
        $unique = [];

        foreach ($sources as $source) {
            $key = $source['path'] . '|' . $source['base_path'] . '|' . $source['namespace'];

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $source;
        }

        return $unique;
    }
}
