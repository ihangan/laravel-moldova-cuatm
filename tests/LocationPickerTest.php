<?php

declare(strict_types=1);

namespace Ihangan\MoldovaCuatm\Tests;

use Ihangan\MoldovaCuatm\Models\Location;
use Ihangan\MoldovaCuatm\Tests\Fixtures\LocationPicker;
use Illuminate\Support\Facades\App;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;

final class LocationPickerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->import();

        App::setLocale('ro');
    }

    #[Test]
    public function it_starts_with_a_single_level_of_top_level_units(): void
    {
        Livewire::test(LocationPicker::class)
            ->assertSee('Chișinău')
            ->assertSee('Anenii Noi')
            ->assertDontSee('Botanica');
    }

    #[Test]
    public function choosing_a_unit_opens_the_level_below_it(): void
    {
        $chisinau = $this->id('chisinau');

        Livewire::test(LocationPicker::class)
            ->call('choose', 0, (string) $chisinau)
            ->assertSet('selected', $chisinau)
            ->assertSee('Botanica')      // the sectors appeared
            ->assertDontSee('Sîngera');  // but not what is below them yet
    }

    #[Test]
    public function it_keeps_opening_levels_all_the_way_down(): void
    {
        $component = Livewire::test(LocationPicker::class)
            ->call('choose', 0, (string) $this->id('chisinau'))
            ->call('choose', 1, (string) $this->id('botanica'))
            ->assertSee('Sîngera')
            ->call('choose', 2, (string) $this->id('singera'))
            ->assertSee('Dobrogea')
            ->call('choose', 3, (string) $this->id('dobrogea'));

        // municipality > sector > town > village
        $component
            ->assertSet('selected', $this->id('dobrogea'))
            ->assertSet('path', [
                $this->id('chisinau'),
                $this->id('botanica'),
                $this->id('singera'),
                $this->id('dobrogea'),
            ]);
    }

    #[Test]
    public function a_village_with_no_children_does_not_open_another_level(): void
    {
        Livewire::test(LocationPicker::class)
            ->call('choose', 0, (string) $this->id('chisinau'))
            ->call('choose', 1, (string) $this->id('botanica'))
            ->call('choose', 2, (string) $this->id('singera'))
            ->call('choose', 3, (string) $this->id('dobrogea'))
            ->assertSee('Dobrogea');
    }

    #[Test]
    public function choosing_again_higher_up_drops_the_deeper_levels(): void
    {
        Livewire::test(LocationPicker::class)
            ->call('choose', 0, (string) $this->id('chisinau'))
            ->call('choose', 1, (string) $this->id('botanica'))
            ->call('choose', 2, (string) $this->id('singera'))
            ->call('choose', 0, (string) $this->id('anenii-noi-raion'))
            ->assertSet('selected', $this->id('anenii-noi-raion'))
            ->assertSet('path', [$this->id('anenii-noi-raion')])
            ->assertDontSee('Sîngera');
    }

    #[Test]
    public function clearing_a_level_clears_the_selection(): void
    {
        Livewire::test(LocationPicker::class)
            ->call('choose', 0, (string) $this->id('chisinau'))
            ->call('choose', 0, '')
            ->assertSet('selected', null)
            ->assertSet('path', []);
    }

    #[Test]
    public function it_reopens_the_whole_chain_for_a_preselected_location(): void
    {
        $component = Livewire::test(LocationPicker::class, ['selected' => $this->id('dobrogea')]);

        $component->assertSet('selected', $this->id('dobrogea'));
        $component->assertSet('path', [
            $this->id('chisinau'),
            $this->id('botanica'),
            $this->id('singera'),
            $this->id('dobrogea'),
        ]);
    }

    #[Test]
    public function it_announces_the_selection_to_the_page(): void
    {
        Livewire::test(LocationPicker::class)
            ->call('choose', 0, (string) $this->id('chisinau'))
            ->assertDispatched('location-selected', locationId: $this->id('chisinau'));
    }

    private function id(string $slug): int
    {
        return Location::query()->where('slug', $slug)->firstOrFail()->id;
    }
}
