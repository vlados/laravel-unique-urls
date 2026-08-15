# Artisan Commands

Both commands look for models in the directories resolved from
[`model_paths`](configuration.md#model-paths) — `app/` plus every module by
default — so a model in `Modules/Blog/app/Models` is picked up just like one in
`app/Models`.

## `urls:generate`

Generate unique URLs for models that use the `HasUniqueUrls` trait.

```bash
php artisan urls:generate [options]
```

### Options

| Option | Description |
|--------|-------------|
| `--model=ModelName` | Target a specific model — a fully qualified class name, or a short name resolved against `App\Models` and then the discovered models |
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

# A model that lives in a module
php artisan urls:generate --model="Modules\Blog\Models\Post"

# Only missing, short name (App\Models first, then the discovered models)
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
php artisan urls:doctor [--model=ModelName] [--strict]
```

### Options

| Option | Description |
|--------|-------------|
| `--model=ModelName` | Check a single model (FQCN or short name) |
| `--strict` | Exit with a failure code when no models were found to check |

### Checks

| Check | Description |
|-------|-------------|
| Conflicting columns | Detects `url` or `urls` columns that clash with the trait |
| Method parameters | Verifies `urlStrategy()` has `$language` and `$locale` params |
| urlHandler output | Validates array has `controller`, `method`, `arguments` keys |
| Controller resolves | Resolves the controller through the `ControllerResolver`, so Livewire component names count as valid |
| Method exists | Mirrors the request handler: `__invoke()` for the Livewire style, otherwise the declared method with `show()`/`index()` as fallbacks |
| Multi-language URLs | Ensures `urlStrategy()` produces different slugs per language |

### Examples

```bash
# Check all models
php artisan urls:doctor

# Check a specific model
php artisan urls:doctor --model=Product
php artisan urls:doctor --model="Modules\Blog\Models\Post"
```

### Output

A clean run says how much was actually looked at:

```
Everything is ok — checked 12 models in 2 paths
```

When nothing matched, the command says so instead of reporting health:

```
No models using the HasUniqueUrls trait were found — nothing was checked.
Scanned 1 path:
  - /var/www/app → <application namespace>
Add the directory to the unique-urls.model_paths config if your models live somewhere else.
```

### CI Integration

```yaml
# .github/workflows/ci.yml
- name: Validate URL configuration
  run: php artisan urls:doctor --strict
```

The command exits with a non-zero code when errors are found, so it will fail your CI pipeline automatically. `--strict` also fails the run when the scan found no models at all — usually a sign that `model_paths` no longer matches where the models live.
