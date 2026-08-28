<?php

declare(strict_types=1);

namespace Ihangan\MoldovaCuatm\Tests;

use Ihangan\MoldovaCuatm\Enums\LocationType;
use Ihangan\MoldovaCuatm\Models\Location;
use PHPUnit\Framework\Attributes\Test;

final class HierarchyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->import();
    }

    #[Test]
    public function a_village_resolves_its_full_parent_chain(): void
    {
        $dobrogea = Location::query()->where('slug', 'dobrogea')->firstOrFail();

        $chain = array_map(
            static fn (Location $location): string => $location->slug,
            $dobrogea->ancestors(),
        );

        $this->assertSame(['singera', 'botanica', 'chisinau'], $chain);
    }

    #[Test]
    public function roots_have_no_parent(): void
    {
        $chisinau = Location::query()->where('slug', 'chisinau')->firstOrFail();

        $this->assertTrue($chisinau->isRoot());
        $this->assertNull($chisinau->parent_id);
    }

    #[Test]
    public function a_sector_lists_its_children(): void
    {
        $botanica = Location::query()->where('slug', 'botanica')->firstOrFail();

        $this->assertTrue($botanica->children->contains('slug', 'singera'));
    }

    #[Test]
    public function no_location_points_at_a_missing_parent(): void
    {
        $orphans = Location::query()
            ->whereNotNull('parent_id')
            ->whereNotIn('parent_id', Location::query()->select('id'))
            ->count();

        $this->assertSame(0, $orphans);
        $this->assertSame(37, Location::query()->roots()->count());
    }

    #[Test]
    public function chisinau_keeps_the_shape_the_classifier_declares(): void
    {
        // The CUATM section for mun. Chişinău declares 5 sectors, 7 towns,
        // 11 communes, 4 localities inside towns and 12 inside communes.
        $chisinau = Location::query()->where('slug', 'chisinau')->firstOrFail();

        $sectors = $chisinau->children;
        $this->assertCount(5, $sectors);
        $this->assertTrue($sectors->every(fn (Location $s): bool => $s->type === LocationType::Sector));

        $levelOne = Location::query()->whereIn('parent_id', $sectors->pluck('id'))->get();
        $this->assertCount(7, $levelOne->where('type', LocationType::Town));
        $this->assertCount(11, $levelOne->where('type', LocationType::Village));

        $levelTwo = Location::query()->whereIn('parent_id', $levelOne->pluck('id'))->get();
        $parentTypes = $levelOne->pluck('type', 'id');
        $this->assertCount(4, $levelTwo->filter(fn (Location $l): bool => $parentTypes[$l->parent_id] === LocationType::Town));
        $this->assertCount(12, $levelTwo->filter(fn (Location $l): bool => $parentTypes[$l->parent_id] === LocationType::Village));
    }
}
