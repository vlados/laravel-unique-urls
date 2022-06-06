<?php

namespace Vlados\LaravelUniqueUrls;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Vlados\LaravelUniqueUrls\Commands\LaravelUniqueUrlsCommand;

class LaravelUniqueUrlsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-unique-urls')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel-unique-urls_table')
            ->hasCommand(LaravelUniqueUrlsCommand::class);
    }
}
