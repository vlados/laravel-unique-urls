<?php

namespace Vlados\LaravelUniqueUrls\Tests;

use File;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use Vlados\LaravelUniqueUrls\LaravelUniqueUrlsController;
use Vlados\LaravelUniqueUrls\LaravelUniqueUrlsServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Vlados\\LaravelUniqueUrls\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );

        $this->setUpDatabase($this->app);
        $this->setUpRoutes($this->app);
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelUniqueUrlsServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        $this->initializeDirectory($this->getTempDirectory());

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => $this->getTempDirectory() . '/database.sqlite',
            'prefix' => '',
        ]);
    }

    protected function initializeDirectory(string $directory)
    {
        if (File::isDirectory($directory)) {
            File::deleteDirectory($directory);
        }
        File::makeDirectory($directory);
    }

    protected function setUpDatabase(Application $app)
    {
        file_put_contents($this->getTempDirectory() . '/database.sqlite', null);
        $migration = include __DIR__ . '/../database/migrations/create_unique_urls_table.php.stub';
        $migration->up();

        Schema::create('test_models', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
        });
        Schema::create('child_models', function (Blueprint $table) {
            $table->increments('id');
            $table->integer("parent_id");
            $table->string('name')->nullable();
        });
    }

    protected function getTempDirectory(): string
    {
        return __DIR__ . '/temp';
    }

    /**
     * @param Application $app
     */
    protected function setUpRoutes(Application $app)
    {
        Route::get('{urlObj}', [
            LaravelUniqueUrlsController::class, 'handleRequest',
        ])
            ->where('urlObj', '.*');
    }
}
