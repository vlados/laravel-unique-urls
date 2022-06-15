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
        if (method_exists($urlObj->controller, $urlObj->method)) {
            $called = $slugController->{$urlObj->method}($request, $arguments ?? []);
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

    public function handleRedirect(Request $request, $arguments = [])
    {
        return redirect($arguments['redirect_to'], config('unique-urls.redirect_http_code', 301));
    }
}
