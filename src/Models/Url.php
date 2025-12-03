<?php

declare(strict_types=1);

namespace Vlados\LaravelUniqueUrls\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Vlados\LaravelUniqueUrls\Exceptions\EmptySlugException;
use Vlados\LaravelUniqueUrls\Exceptions\InvalidSlugException;
use Vlados\LaravelUniqueUrls\LaravelUniqueUrlsController;

/**
 * Vlados\LaravelUniqueUrls\Models\Url.
 *
 * @property string $slug
 * @property string $controller
 * @property string $method
 * @property mixed $arguments
 * @property string $language
 * @property MorphTo $related
 * @property mixed $related_id
 * @property mixed $related_type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Url extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $fillable = ['slug', 'controller', 'method', 'arguments', 'language'];
    protected $casts = [
        'arguments' => 'json',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Generate a slug for given model. If the slug exists for different model it will add suffix _1, _2 and so on.
     *
     * @throws EmptySlugException
     * @throws InvalidSlugException
     * @throws Exception
     */
    public static function makeSlug(string $slug, Model $model): string
    {
        if (! $slug) {
            throw EmptySlugException::forModel($model);
        }

        $originalSlug = $slug;

        // Auto-trim leading and trailing slashes
        $slug = trim($slug, '/');

        // Check if slug is empty after trimming
        if (! $slug) {
            throw EmptySlugException::afterTrimming($originalSlug, $model);
        }

        // Log warning if slug was modified
        if ($originalSlug !== $slug) {
            Log::warning('Slug was trimmed: leading/trailing slashes removed', [
                'model' => get_class($model),
                'id' => $model->getKey(),
                'original' => $originalSlug,
                'trimmed' => $slug,
            ]);
        }

        // Optional: Validate slug format (if config enabled)
        if (config('unique-urls.validate_slugs', false)) {
            self::validateSlugFormat($slug, $model);
        }

        // Check for reserved slugs
        self::checkReservedSlugs($slug, $model);

        $where = $model->only(['id', 'type']);
        $where['type'] = $model::class;
        $new_slug = self::makeUniqueSlug($slug, $where);

        if ($new_slug) {
            return $new_slug;
        }

        throw new Exception('Error creating slug for ' . $model);
    }

    /**
     * Validate slug format (lowercase letters, numbers, hyphens only).
     *
     * @throws InvalidSlugException
     */
    protected static function validateSlugFormat(string $slug, Model $model): void
    {
        // Check for invalid characters
        if (! preg_match('/^[a-z0-9\-]+$/', $slug)) {
            throw InvalidSlugException::containsInvalidCharacters($slug, $model);
        }
    }

    /**
     * Check if slug is in the reserved slugs list.
     *
     * @throws InvalidSlugException
     */
    protected static function checkReservedSlugs(string $slug, Model $model): void
    {
        $reservedSlugs = config('unique-urls.reserved_slugs', []);

        if (! empty($reservedSlugs) && in_array($slug, $reservedSlugs, true)) {
            throw InvalidSlugException::isReserved($slug, $model);
        }
    }

    protected static function booted(): void
    {
        static::updated(callback: static function (Url $url): void {
            if (! $url->isDirty('slug')) {
                return;
            }
            Url::create([
                'controller' => LaravelUniqueUrlsController::class,
                'language' => $url->language,
                'method' => 'handleRedirect',
                'slug' => $url->getOriginal('slug'),
                'arguments' => [
                    'original_model' => $url->related_type,
                    'original_id' => $url->related_id,
                    'redirect_to' => $url->slug,
                ],
            ]);
        });
    }

    private static function makeUniqueSlug($slug, $where)
    {
        $originalSlug = $slug;
        $i = 1;
        while (self::otherRecordExistsWithSlug($slug, $where)) {
            $slug = $originalSlug . '_' . $i;
            ++$i;
        }

        return $slug;
    }

    private static function otherRecordExistsWithSlug(string $path, $whereModel): bool
    {
        $query = self::where(static function ($query) use ($whereModel): void {
            $query->whereNot(static function ($query) use ($whereModel): void {
                $query->where('related_id', $whereModel['id'])
                    ->where('related_type', $whereModel['type']);
            })
                ->orWhere(static function ($query): void {
                    $query->whereNull('related_id')
                        ->whereNull('related_type');
                });
        })
            ->where('slug', $path);

        return $query->exists();
    }
}
