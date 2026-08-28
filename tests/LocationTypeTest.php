<?php

declare(strict_types=1);

namespace Ihangan\MoldovaCuatm\Tests;

use Ihangan\MoldovaCuatm\Enums\LocationType;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Lang;
use PHPUnit\Framework\Attributes\Test;

final class LocationTypeTest extends TestCase
{
    #[Test]
    public function a_type_reads_as_the_language_says_it(): void
    {
        // The code speaks English, the reader does not have to.
        $this->assertSame('district', LocationType::District->label('en'));
        $this->assertSame('raion', LocationType::District->label('ro'));
        $this->assertSame('район', LocationType::District->label('ru'));
        $this->assertSame('район', LocationType::District->label('uk'));

        $this->assertSame('oraș', LocationType::Town->label('ro'));
        $this->assertSame('sat', LocationType::Village->label('ro'));
        $this->assertSame('місто', LocationType::Town->label('uk'));
    }

    #[Test]
    public function it_labels_in_the_current_locale_when_none_is_given(): void
    {
        App::setLocale('ro');

        $this->assertSame('municipiu', LocationType::Municipality->label());
    }

    #[Test]
    public function every_type_is_translated_in_every_shipped_locale(): void
    {
        foreach (LocationType::cases() as $type) {
            foreach (['ro', 'ru', 'uk', 'en'] as $locale) {
                $this->assertTrue(
                    Lang::has("moldova-cuatm::location-types.{$type->value}", $locale),
                    "Missing {$locale} label for {$type->value}.",
                );
            }
        }
    }
}
