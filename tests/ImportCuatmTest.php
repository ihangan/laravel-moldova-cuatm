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
        $this->import();

        $this->assertSame(1721, Location::query()->count());
        $this->assertSame(32, Location::query()->ofType(LocationType::District)->count());
        $this->assertSame(13, Location::query()->ofType(LocationType::Municipality)->count());
        $this->assertSame(5, Location::query()->ofType(LocationType::Sector)->count());
        $this->assertSame(54, Location::query()->ofType(LocationType::Town)->count());
        $this->assertSame(1615, Location::query()->ofType(LocationType::Village)->count());
    }

    #[Test]
    public function running_it_twice_does_not_duplicate_rows(): void
    {
        $this->import();
        $this->import();

        $this->assertSame(1721, Location::query()->count());
    }

    #[Test]
    public function the_fresh_option_replaces_existing_rows(): void
    {
        $this->import();
        $this->import(fresh: true);

        $this->assertSame(1721, Location::query()->count());
    }

    #[Test]
    public function it_stores_the_cuatm_identifier_and_the_statistical_code_separately(): void
    {
        $this->import();

        $chisinau = Location::query()->where('slug', 'chisinau')->firstOrFail();

        $this->assertSame('0100', $chisinau->code);
        $this->assertSame('0101000', $chisinau->statistic_code);

        // The two classifiers number localities independently: Băcioi sits at
        // 0112000 statistically but carries the CUATM identifier 5511.
        $bacioi = Location::query()->where('slug', 'bacioi')->firstOrFail();

        $this->assertSame('5511', $bacioi->code);
        $this->assertSame('0112000', $bacioi->statistic_code);
    }

    #[Test]
    public function every_code_is_a_distinct_four_sign_cuatm_identifier(): void
    {
        $this->import();

        $codes = Location::query()->pluck('code');

        $this->assertCount(1721, $codes->unique());
        $this->assertEmpty($codes->reject(static fn (string $code): bool => preg_match('/^\\d{4}$/', $code) === 1));
        $this->assertCount(1721, Location::query()->pluck('statistic_code')->unique());
    }

    #[Test]
    public function the_top_level_units_are_the_types_the_enum_declares(): void
    {
        $this->import();

        $types = Location::query()->roots()->pluck('type')->unique()->values();

        $this->assertEqualsCanonicalizing(LocationType::roots(), $types->all());
    }

    #[Test]
    public function romanian_names_use_comma_below_diacritics(): void
    {
        $this->import();

        // The classifier is typeset with the Turkish cedilla letters ş/ţ
        // (U+015F/U+0163). Romanian is written with ș/ț (U+0219/U+021B).
        $offenders = Location::query()
            ->pluck('name')
            ->filter(static fn (mixed $name): bool => is_string($name) && preg_match('/[şţŞŢ]/u', $name) === 1);

        $this->assertEmpty($offenders);
    }
}
