<?php

use Vlados\LaravelUniqueUrls\Testing\AssertsUniqueUrls;

beforeEach(function () {
    app()->setLocale('en');
});

// ============================================
// Testing the AssertsUniqueUrls trait
// ============================================

test('35. AssertsUniqueUrls trait exists and can be used', function () {
    expect(trait_exists('Vlados\LaravelUniqueUrls\Testing\AssertsUniqueUrls'))->toBeTrue();
});

test('36. AssertsUniqueUrls has all required assertion methods', function () {
    $reflection = new \ReflectionClass('Vlados\LaravelUniqueUrls\Testing\AssertsUniqueUrls');
    $methods = $reflection->getMethods(\ReflectionMethod::IS_PROTECTED);
    $methodNames = array_map(fn ($m) => $m->getName(), $methods);

    expect($methodNames)->toContain('assertHasUrl')
        ->and($methodNames)->toContain('assertHasNoUrl')
        ->and($methodNames)->toContain('assertNoLeadingSlash')
        ->and($methodNames)->toContain('assertNoTrailingSlash')
        ->and($methodNames)->toContain('assertUrlIsAccessible')
        ->and($methodNames)->toContain('assertHasUrlForLanguage')
        ->and($methodNames)->toContain('assertUrlEquals')
        ->and($methodNames)->toContain('assertSlugIsUnique')
        ->and($methodNames)->toContain('assertUrlContains')
        ->and($methodNames)->toContain('assertUrlMatchesPattern')
        ->and($methodNames)->toContain('assertHasUrlsForAllLanguages');
});
