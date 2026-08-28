<?php

declare(strict_types=1);

namespace Ihangan\MoldovaCuatm\Tests;

use Ihangan\MoldovaCuatm\Enums\LocationType;
use Ihangan\MoldovaCuatm\Models\Location;
use PHPUnit\Framework\Attributes\Test;

final class LocationModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->import();
    }

    #[Test]
    public function it_returns_names_in_each_locale(): void
    {
        $chisinau = Location::query()->where('slug', 'chisinau')->firstOrFail();

        $this->assertSame('Chișinău', $chisinau->getTranslation('name', 'ro'));
        $this->assertSame('Кишинёв', $chisinau->getTranslation('name', 'ru'));
        $this->assertSame('Кишинів', $chisinau->getTranslation('name', 'uk'));
        $this->assertSame('Chisinau', $chisinau->getTranslation('name', 'en'));
    }

    #[Test]
    public function a_missing_locale_falls_back_to_romanian(): void
    {
        // A village outside the curated set has no English translation, so the
        // English read should hand back the Romanian name.
        $village = Location::query()->ofType(LocationType::Village)->firstOrFail();

        $this->assertSame(
            $village->getTranslation('name', 'ro'),
            $village->getTranslation('name', 'en'),
        );
    }

    #[Test]
    public function the_type_is_cast_to_an_enum(): void
    {
        $chisinau = Location::query()->where('slug', 'chisinau')->firstOrFail();

        $this->assertSame(LocationType::Municipality, $chisinau->type);
    }

    #[Test]
    public function it_carries_coordinates(): void
    {
        $chisinau = Location::query()->where('slug', 'chisinau')->firstOrFail();

        $this->assertNotNull($chisinau->lat);
        $this->assertNotNull($chisinau->lng);
    }
}
