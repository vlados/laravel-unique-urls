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
        $arguments = $urlObj->arguments;
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
}
