# Changelog

All notable changes to `laravel-unique-urls` will be documented in this file.

## v1.1.3 - 2026-01-11

### Laravel 12 Compatibility

This release fixes compatibility with Laravel 12.

#### What Changed

The `asJson()` method signature changed in Laravel 12 to include a new `$flags` parameter:

```php
// Laravel 11
protected function asJson($value): string|false

// Laravel 12
protected function asJson($value, $flags = 0): string|false
```

This update ensures the package works with both Laravel 11 and Laravel 12.

#### Supported Versions

- **Laravel:** 9, 10, 11, 12
- **PHP:** 8.1, 8.2, 8.3, 8.4

---

**Full Changelog**: https://github.com/vlados/laravel-unique-urls/compare/v1.1.2...v1.1.3

## v1.1.2 - 2026-01-11

### Performance Improvement

This release moves the conflicting column validation from runtime to the `urls:doctor` command, eliminating redundant database schema queries on every model instantiation.

#### What Changed

Previously, every time a model using the `HasUniqueUrls` trait was instantiated, 2 schema queries were executed to check for conflicting `url` and `urls` columns. In loops or batch operations, this caused significant overhead.

Now, this validation only runs when you explicitly call:

```bash
php artisan urls:doctor


```
#### Migration Guide

No changes required. The check is now part of the `urls:doctor` command, which you can run:

- During development to catch misconfigurations
- In your CI pipeline before deployment

#### Changes

- **Removed** `initializeHasUniqueUrls()`, `checkForConflictingAttributes()`, and `hasColumn()` from `HasUniqueUrls` trait
- **Added** `checkConflictingColumns()` to `UrlsDoctorCommand`
- **Updated** tests to verify the doctor command catches conflicts

#### Performance Impact

| Scenario | Before | After |
|----------|--------|-------|
| Single model instantiation | 2 schema queries | 0 queries |
| Loop with 1000 models | 2000 schema queries | 0 queries |


---

**Full Changelog**: https://github.com/vlados/laravel-unique-urls/compare/v1.1.1...v1.1.2

## v1.1.1 - 2025-12-03

**Full Changelog**: https://github.com/vlados/laravel-unique-urls/compare/v1.1.0...v1.1.1

## v1.1.0 - 2025-12-03

### What's Changed

* Add Claude Code GitHub Workflow by @vlados in https://github.com/vlados/laravel-unique-urls/pull/150
* docs: add getSlug() method and usage examples to API documentation by @vlados in https://github.com/vlados/laravel-unique-urls/pull/151
* fix: change slug column from varchar(255) to text by @vlados in https://github.com/vlados/laravel-unique-urls/pull/152
* feat: add exception for models with conflicting url/urls columns by @vlados in https://github.com/vlados/laravel-unique-urls/pull/154
* Bump dependabot/fetch-metadata from 2.2.0 to 2.4.0 by @dependabot[bot] in https://github.com/vlados/laravel-unique-urls/pull/159
* Bump actions/checkout from 4 to 6 by @dependabot[bot] in https://github.com/vlados/laravel-unique-urls/pull/169
* Bump stefanzweifel/git-auto-commit-action from 5 to 7 by @dependabot[bot] in https://github.com/vlados/laravel-unique-urls/pull/162

**Full Changelog**: https://github.com/vlados/laravel-unique-urls/compare/v0.4.1...v1.1.0

## v1.0.0 - 2025-10-01

### Added

- Laravel 12 support
- Laravel 11 support
- PHP 8.4 support

### Changed

- Updated to Pest v3 for testing
- Updated minimum PHP version to 8.1
- Upgraded testing dependencies (PHPUnit v11, Orchestra Testbench v9)
- Migrated from nunomaduro/larastan to larastan/larastan
- Updated Faker API usage in tests to use `fake()` helper

### Breaking Changes

- Dropped support for Laravel 8 and below
- Requires PHP 8.1 or higher

## v.0.4.0 - 2022-09-08

### What's Changed

- feat: add translatable urls by @vlados in https://github.com/vlados/laravel-unique-urls/pull/11
- fix: some bugs by @vlados in https://github.com/vlados/laravel-unique-urls/pull/12
- If there is multiple redirections, to redirect only to the active url

### New Contributors

- @vlados made their first contribution in https://github.com/vlados/laravel-unique-urls/pull/11

**Full Changelog**: https://github.com/vlados/laravel-unique-urls/compare/v0.3.2...v0.4.0

## v0.3.2  - 2022-08-26

- Fix bug when using with filamentphp

## v0.3.1 - 2022-06-30

add HasUniqueUrlAttributes

## v0.3.0 - 2022-06-15

**Full Changelog**: https://github.com/vlados/laravel-unique-urls/compare/v0.2.0...v0.3.0

## v0.2.0 - 2022-06-08

**Full Changelog**: https://github.com/vlados/laravel-unique-urls/compare/v0.1.0...v0.2.0
