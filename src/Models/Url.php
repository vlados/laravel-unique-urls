<?php

namespace Vlados\LaravelUniqueUrls\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Url extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $fillable = ['slug', 'controller', 'method', 'arguments', 'language'];
    protected $casts = [
        'arguments' => 'json',
    ];

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function related()
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

        throw new Exception('Error creating slug for '.$model);
    }

    private static function makeUniqueSlug($slug, $where)
    {
        $originalSlug = $slug;
        $i = 1;
        while (self::otherRecordExistsWithSlug($slug, $where)) {
            $slug = $originalSlug.'_'.$i;
            ++$i;
        }

        return $slug;
    }

    private static function otherRecordExistsWithSlug(string $slug, $whereModel): bool
    {
        $query = self::whereNot(function ($query) use ($whereModel) {
            $query->where('related_id', $whereModel['id'])
                ->where('related_type', $whereModel['type'])
            ;
        })
            ->where('slug', $slug)
            ->withoutGlobalScopes()
        ;

        return $query->exists();
    }
}
