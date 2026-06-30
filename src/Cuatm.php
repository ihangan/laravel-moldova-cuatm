<?php

declare(strict_types=1);

namespace Ihangan\MoldovaCuatm;

use Ihangan\MoldovaCuatm\Enums\LocationType;
use Ihangan\MoldovaCuatm\Models\Location;
use Illuminate\Database\Eloquent\Collection;

/**
 * Small query helper over the location table. Anything more involved is just a
 * plain Eloquent query against {@see Location}.
 */
final class Cuatm
{
    public function findByCode(string $code): ?Location
    {
        return Location::query()->whereCode($code)->first();
    }

    public function findBySlug(string $slug): ?Location
    {
        return Location::query()->where('slug', $slug)->first();
    }

    /**
     * @return Collection<int, Location>
     */
    public function ofType(LocationType $type): Collection
    {
        return Location::query()->ofType($type)->orderBy('sort_order')->get();
    }

    /**
     * Top-level units: every raion, municipality and special region. The entry
     * point for a cascading picker (drill down with {@see childrenOf()}).
     *
     * @return Collection<int, Location>
     */
    public function roots(): Collection
    {
        return Location::query()->roots()->orderBy('sort_order')->get();
    }

    /**
     * @return Collection<int, Location>
     */
    public function raioane(): Collection
    {
        return $this->ofType(LocationType::Raion);
    }

    /**
     * @return Collection<int, Location>
     */
    public function municipalities(): Collection
    {
        return $this->ofType(LocationType::Municipality);
    }

    /**
     * @return Collection<int, Location>
     */
    public function childrenOf(Location|int $parent): Collection
    {
        $parentId = $parent instanceof Location ? $parent->id : $parent;

        return Location::query()
            ->where('parent_id', $parentId)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Top-level units with their immediate children eager-loaded.
     *
     * @return Collection<int, Location>
     */
    public function tree(): Collection
    {
        return Location::query()
            ->roots()
            ->with('children')
            ->orderBy('sort_order')
            ->get();
    }
}
