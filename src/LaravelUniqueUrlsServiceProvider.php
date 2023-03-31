<?php

namespace Vlados\LaravelUniqueUrls;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Vlados\LaravelUniqueUrls\Commands\UrlsDoctorCommand;
use Vlados\LaravelUniqueUrls\Commands\UrlsGenerateCommand;

class LaravelUniqueUrlsServiceProvider extends PackageServiceProvider
{
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
