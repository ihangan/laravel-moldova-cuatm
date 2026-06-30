<?php

declare(strict_types=1);

namespace Ihangan\MoldovaCuatm\Tests;

use Ihangan\MoldovaCuatm\Enums\LocationType;
use Ihangan\MoldovaCuatm\Models\Location;
use PHPUnit\Framework\Attributes\Test;

final class ImportCuatmTest extends TestCase
{
    #[Test]
    public function it_imports_every_locality_with_the_expected_type_counts(): void
    {
        $this->artisan('cuatm:import')->assertSuccessful();

        $this->assertSame(1721, Location::query()->count());
        $this->assertSame(32, Location::query()->ofType(LocationType::Raion)->count());
        $this->assertSame(13, Location::query()->ofType(LocationType::Municipality)->count());
        $this->assertSame(5, Location::query()->ofType(LocationType::Sector)->count());
        $this->assertSame(54, Location::query()->ofType(LocationType::Town)->count());
        $this->assertSame(1615, Location::query()->ofType(LocationType::Village)->count());
    }

    #[Test]
    public function running_it_twice_does_not_duplicate_rows(): void
    {
        $this->artisan('cuatm:import')->assertSuccessful();
        $this->artisan('cuatm:import')->assertSuccessful();

        $this->assertSame(1721, Location::query()->count());
    }

    #[Test]
    public function the_fresh_option_replaces_existing_rows(): void
    {
        $this->artisan('cuatm:import')->assertSuccessful();
        $this->artisan('cuatm:import', ['--fresh' => true])->assertSuccessful();

        $this->assertSame(1721, Location::query()->count());
    }

    #[Test]
    public function it_stores_the_official_cuatm_code(): void
    {
        $this->artisan('cuatm:import')->assertSuccessful();

        $chisinau = Location::query()->where('slug', 'chisinau')->firstOrFail();

        $this->assertNotEmpty($chisinau->code);
    }
}
