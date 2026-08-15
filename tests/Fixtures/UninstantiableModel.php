<?php

namespace Vlados\LaravelUniqueUrls\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Vlados\LaravelUniqueUrls\HasUniqueUrls;
use Vlados\LaravelUniqueUrls\Tests\TestUrlHandler;

/**
 * A model the container cannot build: its constructor requires an interface
 * that is deliberately never bound.
 *
 * Eloquent models are normally instantiated with no arguments, but a model with
 * a promoted dependency is legal and appears in real projects. Before the
 * per-model try/catch in UrlsDoctorCommand::checkModel(), a single model like
 * this ended the whole run with an unhandled BindingResolutionException, so every
 * model discovered after it went unchecked — and the operator saw a crash rather
 * than a report.
 */
class UninstantiableModel extends Model
{
    use HasUniqueUrls;

    protected $table = 'uninstantiable_models';

    public function __construct(MissingDependency $dependency, array $attributes = [])
    {
        parent::__construct($attributes);
    }

    public function urlHandler(): array
    {
        return [
            'controller' => TestUrlHandler::class,
            'method' => '__invoke',
            'arguments' => [],
        ];
    }
}
