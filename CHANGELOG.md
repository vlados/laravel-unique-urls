# Changelog

All notable changes to `laravel-unique-urls` will be documented in this file.

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
