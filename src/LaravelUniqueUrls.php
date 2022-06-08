<?php

namespace Vlados\LaravelUniqueUrls;

use Illuminate\Http\Request;
use Vlados\LaravelUniqueUrls\Models\Url;

class LaravelUniqueUrls
{
    public function handleRequest(Url $urlObj, Request $request)
    {
        if (! (isset($urlObj->controller) && class_exists($urlObj->controller))) {
            abort('404');
        }

        $slugController = new $urlObj->controller();
        $arguments = $urlObj->getAttribute('arguments');
        if (method_exists($slugController, '__invoke') && $urlObj->getAttribute('method') === '') {
            // if it is livewire
            $request->route()->setParameter('arguments', $arguments);

            return \App::call([$slugController, '__invoke']);
        }
        $arguments['related'] = $urlObj->related;
        if (isset($urlObj->method, $arguments) && method_exists($urlObj->controller, $urlObj->method)) {
            $called = $slugController->{$urlObj->method}($request, $arguments);
        } elseif (isset($urlObj->method) && ! isset($arguments) && method_exists($urlObj->controller, $urlObj->method)) {
            $called = $slugController->{$urlObj->method}($request);
        } elseif (! isset($urlObj->method) && isset($arguments) && method_exists($urlObj->controller, 'show')) {
            $called = $slugController->show($arguments);
        } elseif (! isset($urlObj->method) && ! isset($arguments) && method_exists($urlObj->controller, 'index')) {
            $called = $slugController->index($request);
        }
        if (isset($called) && false !== $called) {
            return $called;
        }
        abort('404');
    }

    public function handleRedirect($request, $arguments = [])
    {
        return redirect($arguments['redirect_to'], config('unique-urls.redirect_http_code', 301));
    }
}
