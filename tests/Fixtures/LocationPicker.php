<?php

declare(strict_types=1);

namespace Ihangan\MoldovaCuatm\Tests\Fixtures;

use Ihangan\MoldovaCuatm\Cuatm;
use Ihangan\MoldovaCuatm\Models\Location;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

/**
 * Cascading location picker: one select per level, a new one appearing as long
 * as the chosen unit still has children.
 */
final class LocationPicker extends Component
{
    /** @var list<int> the chosen location id at each level */
    public array $path = [];

    public ?int $selected = null;

    public function mount(?int $selected = null): void
    {
        if ($selected === null) {
            return;
        }

        $location = Location::query()->find($selected);

        if (! $location instanceof Location) {
            return;
        }

        $this->path = array_map(
            static fn (Location $step): int => $step->id,
            [...array_reverse($location->ancestors()), $location],
        );
        $this->selected = $location->id;
    }

    /**
     * Choosing at a level drops every deeper level: pick another district and
     * the locality under the old one is gone.
     */
    public function choose(int $level, ?string $id): void
    {
        $this->path = array_slice($this->path, 0, $level);

        if ($id !== null && $id !== '') {
            $this->path[] = (int) $id;
        }

        $this->selected = $this->path === [] ? null : $this->path[count($this->path) - 1];

        $this->dispatch('location-selected', locationId: $this->selected);
    }

    public function render(Cuatm $cuatm): View
    {
        return view('location-picker', ['levels' => $this->levels($cuatm)]);
    }

    /**
     * Every level to show: the roots, then the children of each chosen unit,
     * stopping at the first one that has none.
     *
     * @return list<Collection<int, Location>>
     */
    private function levels(Cuatm $cuatm): array
    {
        $levels = [$cuatm->roots()];

        foreach ($this->path as $id) {
            $children = $cuatm->childrenOf($id);

            if ($children->isEmpty()) {
                break;
            }

            $levels[] = $children;
        }

        return $levels;
    }
}
