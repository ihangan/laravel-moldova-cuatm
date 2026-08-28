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
        $this->import();
    }

    #[Test]
    public function it_finds_a_locality_by_its_cuatm_identifier(): void
    {
        $location = Cuatm::findByCode('0112');

        $this->assertNotNull($location);
        $this->assertSame('dobrogea', $location->slug);
    }

    #[Test]
    public function it_finds_the_same_locality_by_its_statistical_code(): void
    {
        $location = Cuatm::findByStatisticCode('0111001');

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
    public function it_lists_all_districts(): void
    {
        $this->assertCount(32, Cuatm::districts());
    }

    #[Test]
    public function it_lists_every_top_level_unit(): void
    {
        // 32 districts + 3 municipalities + Gagauzia + Transnistria.
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
