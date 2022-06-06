<?php

namespace Vlados\LaravelUniqueUrls;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelUniqueUrlsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-unique-urls')
            ->hasConfigFile()
            ->hasRoute('web')
            ->hasMigration('create_laravel-unique-urls_table');
    }
}
