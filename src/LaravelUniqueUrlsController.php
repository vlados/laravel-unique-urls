<?php

namespace Vlados\LaravelUniqueUrls;

use App;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Vlados\LaravelUniqueUrls\Models\Url;

class LaravelUniqueUrlsController
{
    public function handleRequest(Url $urlObj, Request $request)
    {
        if (! (isset($urlObj->controller) && class_exists($urlObj->controller))) {
            abort('404');
        }
        app()->setLocale($urlObj->language);

        $slugController = new $urlObj->controller();
        $arguments = $urlObj->getAttribute('arguments');
        if (method_exists($slugController, '__invoke') && $urlObj->getAttribute('method') === '') {
            // if it is livewire
            $request->route()->setParameter('arguments', $arguments);

            return App::call([$slugController, '__invoke']);
        }
        $arguments['related'] = $urlObj->related;
        if (method_exists($urlObj->controller, $urlObj->method)) {
            $called = $slugController->{$urlObj->method}($request, $arguments ?? [], $urlObj);
        } elseif (method_exists($urlObj->controller, 'show')) {
            $called = $slugController->show($request, $arguments ?? []);
        } elseif (method_exists($urlObj->controller, 'index')) {
            $called = $slugController->index($request, $arguments ?? []);
        }
        if (isset($called) && $called !== false) {
            return $called;
        }
        abort('404');
    }

    /**
     * @param Request $request
     * @param array $arguments
     * @param Url|null $originalUrl
     * @return Redirector|RedirectResponse|Application
     */
    public function handleRedirect(Request $request, array $arguments = [], ?Url $originalUrl = null): Redirector|Application|RedirectResponse
    {
        $url = Url::where("related_type", $arguments["original_model"])
            ->where("related_id", $arguments["original_id"])
            ->where("language", $originalUrl->language)
            ->first();
        if ($url) {
            return redirect(to: $url->slug, status: config('unique-urls.redirect_http_code', 301));
        } else {
            return abort(404);
        }
    }
}
