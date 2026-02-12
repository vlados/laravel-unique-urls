# Troubleshooting

## Slash-Related 404s

**Symptom:** URLs with leading/trailing slashes return 404.

Since v1.1.0 slashes are auto-trimmed, but the root cause is usually a `urlStrategy()` that concatenates nullable values:

```php
// Produces "/product" when parent is null
return $this->parent?->getSlug() . '/' . Str::slug($this->name);
```

Fix by guarding the prefix:

```php
public function urlStrategy($language, $locale): string
{
    $parentSlug = $this->category?->getSlug($language);

    return $parentSlug
        ? $parentSlug . '/' . Str::slug($this->name, '-', $locale)
        : Str::slug($this->name, '-', $locale);
}
```

## Empty Slug Exceptions

```
Cannot generate URL: empty slug for App\Models\Product (ID: 123).
```

Common causes:
1. `urlStrategy()` returns an empty string or null
2. The slug is only slashes (e.g. `'/'`) — empty after trimming
3. The model attribute used for the slug is empty

Add a guard in your strategy:

```php
public function urlStrategy($language, $locale): string
{
    if (empty($this->name)) {
        throw new \RuntimeException("Cannot generate URL: product name is empty (ID: {$this->id})");
    }

    return Str::slug($this->name, '-', $locale);
}
```

## TypeError with `url()` Helper

```
TypeError: Argument #2 ($url) must be of type ?string, Illuminate\Routing\UrlGenerator given
```

This happens when passing `null` to the `url()` helper. Since v1.1.0 `getSlug()` returns an empty string instead of null, but on older versions use the null coalescing operator:

```php
$url = url($model->relative_url ?? '');
```

## URLs Not Generating

Checklist:
1. Does the model use the `HasUniqueUrls` trait?
2. Are `urlStrategy()` and `urlHandler()` implemented?
3. Is `isAutoGenerateUrls()` returning `true` (or not overridden)?
4. Is the catch-all route registered at the **end** of `routes/web.php`?
5. Have you run the migration? (`php artisan migrate`)

Force generation for models with auto-generate disabled:

```bash
php artisan urls:generate --model=Product --force
```

## Reserved Slug Errors

```
Invalid slug 'admin' for App\Models\Page (ID: 1): slug is reserved.
```

This only occurs when `validate_slugs` is enabled in config. Either choose a different slug or remove the entry from the reserved list:

```php
// config/unique-urls.php
'reserved_slugs' => [
    // 'admin', // removed
    'api',
    'login',
],
```
