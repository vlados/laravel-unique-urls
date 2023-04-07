<?php

namespace Vlados\LaravelUniqueUrls\Services;

use Illuminate\Support\Facades\Route;
use Vlados\LaravelUniqueUrls\Models\Url;

class SharedDataService
{
    protected ?Url $data = null;
    public ?string $currentRoute;

    public function __construct()
    {
        $this->currentRoute = Route::currentRouteName();
    }

    public function setData(Url $data): void
    {
        $this->data = $data;
    }

    public function getData(): ?Url
    {
        return $this->data;
    }
}
