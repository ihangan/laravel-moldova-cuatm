<?php

declare(strict_types=1);

namespace Ihangan\MoldovaCuatm\Tests;

use Ihangan\MoldovaCuatm\Enums\LocationType;
use Ihangan\MoldovaCuatm\Facades\Cuatm;
use PHPUnit\Framework\Attributes\Test;

final class CuatmFacadeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('cuatm:import')->assertSuccessful();
    }

    #[Test]
    public function it_finds_a_locality_by_code(): void
    {
        $location = Cuatm::findByCode('0111001');

        $this->assertNotNull($location);
        $this->assertSame('dobrogea', $location->slug);
    }

    #[Test]
    public function it_finds_a_locality_by_slug(): void
    {
        $location = Cuatm::findBySlug('chisinau');

        $this->assertNotNull($location);
        $this->assertSame(LocationType::Municipality, $location->type);
    }

    #[Test]
    public function it_lists_all_raioane(): void
    {
        $this->assertCount(32, Cuatm::raioane());
    }

    #[Test]
    public function it_lists_every_top_level_unit(): void
    {
        // 32 raioane + 3 municipalities + Gagauzia + Transnistria.
        $this->assertCount(37, Cuatm::roots());
        $this->assertTrue(Cuatm::roots()->every(fn ($location): bool => $location->isRoot()));
    }

    #[Test]
    public function the_tree_returns_roots_with_children(): void
    {
        $tree = Cuatm::tree();

        $chisinau = $tree->firstWhere('slug', 'chisinau');

        $this->assertNotNull($chisinau);
        $this->assertTrue($chisinau->relationLoaded('children'));
        $this->assertTrue($chisinau->children->isNotEmpty());
    }
}
