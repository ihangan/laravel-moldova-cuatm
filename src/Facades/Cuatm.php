<?php

declare(strict_types=1);

namespace Ihangan\MoldovaCuatm\Facades;

use Ihangan\MoldovaCuatm\Cuatm as CuatmService;
use Ihangan\MoldovaCuatm\Enums\LocationType;
use Ihangan\MoldovaCuatm\Models\Location;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Location|null findByCode(string $code)
 * @method static Location|null findByStatisticCode(string $code)
 * @method static Location|null findBySlug(string $slug)
 * @method static Collection<int, Location> ofType(LocationType $type)
 * @method static Collection<int, Location> roots()
 * @method static Collection<int, Location> districts()
 * @method static Collection<int, Location> municipalities()
 * @method static Collection<int, Location> childrenOf(Location|int $parent)
 * @method static Collection<int, Location> tree()
 *
 * @see CuatmService
 */
final class Cuatm extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CuatmService::class;
    }
}
