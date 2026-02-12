# Artisan Commands

## `urls:generate`

Generate unique URLs for models that use the `HasUniqueUrls` trait.

```bash
php artisan urls:generate [options]
```

### Options

| Option | Description |
|--------|-------------|
| `--model=ModelName` | Target a specific model (FQCN or short name) |
| `--fresh` | Truncate the URLs table and regenerate everything |
| `--only-missing` | Skip models that already have URLs |
| `--chunk-size=500` | Records per processing chunk (default: 500) |
| `--force` | Generate even if `isAutoGenerateUrls()` returns false |

### Examples

```bash
# All models
php artisan urls:generate

# Specific model
php artisan urls:generate --model="App\Models\Product"

# Only missing, short name
php artisan urls:generate --model=Product --only-missing

# Fresh with custom chunk size
php artisan urls:generate --fresh --chunk-size=1000

# Force for models with auto-generate disabled
php artisan urls:generate --model=Product --force
```

### Output

```
Generating URLs for App\Models\Product...

|- Found: 14,031 models
|- With URLs: 14,030
|- Without URLs: 1

 1/1 [============================] 100% | Generating...

|- Generated: 1 URL
|- Completed

=======================================
           Summary
=======================================
  Generated: 1 URLs
  Duration: 0.5s
=======================================

All done
```

If `isAutoGenerateUrls()` returns false, you'll see a warning:

```
! App\Models\Product has isAutoGenerateUrls() = false
  URLs will not be generated automatically.
  Use --force flag to generate anyway, or enable in model.
```

---

## `urls:doctor`

Validate model configuration and detect common issues. Run during development or in CI.

```bash
php artisan urls:doctor [--model=ModelName]
```

### Checks

| Check | Description |
|-------|-------------|
| Conflicting columns | Detects `url` or `urls` columns that clash with the trait |
| Method parameters | Verifies `urlStrategy()` has `$language` and `$locale` params |
| urlHandler output | Validates array has `controller`, `method`, `arguments` keys |
| Controller exists | Checks the specified controller class exists |
| Method exists | Verifies the controller method exists |
| Multi-language URLs | Ensures `urlStrategy()` produces different slugs per language |

### Examples

```bash
# Check all models
php artisan urls:doctor

# Check a specific model
php artisan urls:doctor --model=Product
```

### CI Integration

```yaml
# .github/workflows/ci.yml
- name: Validate URL configuration
  run: php artisan urls:doctor
```

The command exits with a non-zero code when errors are found, so it will fail your CI pipeline automatically.
