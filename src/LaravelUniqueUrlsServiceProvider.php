<?php

namespace Vlados\LaravelUniqueUrls;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Vlados\LaravelUniqueUrls\Commands\RebuildUrlsCommand;

class LaravelUniqueUrlsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-unique-urls')
            ->hasConfigFile()
            ->hasCommand(RebuildUrlsCommand::class)
            ->hasMigration('create_unique_urls_table');
    }
}
