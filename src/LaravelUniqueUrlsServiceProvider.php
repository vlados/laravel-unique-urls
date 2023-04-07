<?php

declare(strict_types=1);

namespace Vlados\LaravelUniqueUrls;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Vlados\LaravelUniqueUrls\Commands\UrlsDoctorCommand;
use Vlados\LaravelUniqueUrls\Commands\UrlsGenerateCommand;
use Vlados\LaravelUniqueUrls\Services\SharedDataService;

class LaravelUniqueUrlsServiceProvider extends PackageServiceProvider
{
    public function register()
    {
        $this->app->singleton(SharedDataService::class, function ($app) {
            return new SharedDataService();
        });
        parent::register();
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-unique-urls')
            ->hasConfigFile()
            ->hasCommand(UrlsGenerateCommand::class)
            ->hasCommand(UrlsDoctorCommand::class)
            ->hasMigration('create_unique_urls_table');
    }
}
