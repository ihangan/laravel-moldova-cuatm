<?php

declare(strict_types=1);

namespace Ihangan\MoldovaCuatm\Models;

use Ihangan\MoldovaCuatm\Enums\LocationType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * A single administrative-territorial unit from the Moldovan CUATM classifier.
 *
 * @property int $id
 * @property int|null $parent_id
 * @property string $code
 * @property string $name
 * @property string $slug
 * @property LocationType $type
 * @property string|null $lat
 * @property string|null $lng
 * @property int $sort_order
 * @property-read Location|null $parent
 * @property-read Collection<int, Location> $children
 */
class Location extends Model
{
    use HasTranslations;

    /** @var list<string> */
    public array $translatable = ['name'];

    /** @var list<string> */
    protected $fillable = [
        'parent_id',
        'code',
        'name',
        'slug',
        'type',
        'lat',
        'lng',
        'sort_order',
    ];

    public function getTable(): string
    {
        $table = config('moldova-cuatm.table', 'cuatm_locations');

        return is_string($table) ? $table : 'cuatm_locations';
    }

    public function getConnectionName(): ?string
    {
        $connection = config('moldova-cuatm.connection');

        return is_string($connection) ? $connection : parent::getConnectionName();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => LocationType::class,
            'lat' => 'decimal:6',
            'lng' => 'decimal:6',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<self, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<self, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Walk up the tree, nearest parent first.
     *
     * @return list<self>
     */
    public function ancestors(): array
    {
        $chain = [];
        $current = $this->parent;

        while ($current !== null) {
            $chain[] = $current;
            $current = $current->parent;
        }

        return $chain;
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOfType(Builder $query, LocationType $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeWhereCode(Builder $query, string $code): Builder
    {
        return $query->where('code', $code);
    }
}
