<?php

declare(strict_types=1);

namespace Vlados\LaravelUniqueUrls;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Vlados\LaravelUniqueUrls\Models\Url;
use Vlados\LaravelUniqueUrls\Services\SharedDataService;

class LaravelUniqueUrlsController
{
    private SharedDataService $sharedDataService;

    public function __construct(SharedDataService $sharedDataService)
    {
        $this->sharedDataService = $sharedDataService;
    }

    /**
     * Handles the incoming request by checking if the controller exists, then
     * sets the locale and attempts to call the appropriate controller method.
     */
    public function handleRequest(Url $urlObj, Request $request)
    {
        if (! (isset($urlObj->controller) && class_exists($urlObj->controller))) {
            abort(404);
        }
        app()->setLocale($urlObj->language);

        $slugController = new $urlObj->controller();
        $arguments = $urlObj->getAttribute('arguments') ?? [];

        $called = $this->callControllerMethod($slugController, $urlObj, $request, $arguments);

        if (isset($called) && $called !== false) {
            $this->sharedDataService->setData($urlObj);

            return $called;
        }

        abort(404);
    }

    private function callControllerMethod($slugController, Url $urlObj, Request $request, array $arguments)
    {
        if ($this->isLivewire($slugController, $urlObj)) {
            return $this->callLivewire($slugController, $request, $arguments);
        }

        $arguments['related'] = $urlObj->related;

        if (method_exists($urlObj->controller, $urlObj->method)) {
            return $this->callCustomMethod($slugController, $urlObj, $request, $arguments);
        }

        if (method_exists($urlObj->controller, 'show')) {
            return $this->callShow($slugController, $request, $arguments);
        }

        if (method_exists($urlObj->controller, 'index')) {
            return $this->callIndex($slugController, $request, $arguments);
        }

        return null;
    }

    private function isLivewire($slugController, Url $urlObj): bool
    {
        return method_exists($slugController, '__invoke') && $urlObj->getAttribute('method') === '';
    }

    private function callLivewire($slugController, Request $request, array $arguments)
    {
        $request->route()->setParameter('arguments', $arguments);

        return app()->call([$slugController, '__invoke']);
    }

    private function callCustomMethod($slugController, Url $urlObj, Request $request, array $arguments)
    {
        return $slugController->{$urlObj->method}($request, $arguments, $urlObj);
    }

    private function callShow($slugController, Request $request, array $arguments)
    {
        return $slugController->show($request, $arguments);
    }

    private function callIndex($slugController, Request $request, array $arguments)
    {
        return $slugController->index($request, $arguments);
    }

    /**
     * Handles redirecting the request based on the given arguments and
     * original URL.
     *
     * @param Request $request
     * @param array $arguments
     * @param Url|null $originalUrl
     * @return Redirector|Application|RedirectResponse
     */
    public function handleRedirect(Request $request, array $arguments = [], ?Url $originalUrl = null): Redirector|Application|RedirectResponse
    {
        $url = Url::where('related_type', $arguments['original_model'])
            ->where('related_id', $arguments['original_id'])
            ->where('language', $originalUrl->language)
            ->first();

        if ($url) {
            return redirect(to: $url->slug, status: config('unique-urls.redirect_http_code', 301));
        }

        return abort(404);
    }
}
