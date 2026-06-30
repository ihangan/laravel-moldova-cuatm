<?php

declare(strict_types=1);

namespace Ihangan\MoldovaCuatm\Tests;

use Ihangan\MoldovaCuatm\Models\Location;
use PHPUnit\Framework\Attributes\Test;

final class HierarchyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('cuatm:import')->assertSuccessful();
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
}
