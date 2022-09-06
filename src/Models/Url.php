<?php

namespace Vlados\LaravelUniqueUrls\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
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
     * @throws Exception
     */
    public static function makeSlug(string $slug, Model $model): string
    {
        if (! $slug) {
            throw new Exception('Slug cannot be empty');
        }
        $where = $model->only(['id', 'type']);
        $where['type'] = $model::class;
        $new_slug = self::makeUniqueSlug($slug, $where);
        if ($new_slug) {
            return $new_slug;
        }

        throw new Exception('Error creating slug for ' . $model);
    }

    protected static function booted()
    {
        static::updated(callback: function (Url $url) {
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
        $query = self::where(function ($query) use ($whereModel) {
            $query->whereNot(function ($query) use ($whereModel) {
                $query->where('related_id', $whereModel['id'])
                    ->where('related_type', $whereModel['type']);
            })
                ->orWhere(function ($query): void {
                    $query->whereNull('related_id')
                        ->whereNull('related_type');
                });
        })
            ->where('slug', $path);

        return $query->exists();
    }
}
