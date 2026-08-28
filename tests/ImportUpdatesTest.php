<?php

declare(strict_types=1);

namespace Ihangan\MoldovaCuatm\Tests;

use Ihangan\MoldovaCuatm\Enums\LocationType;
use Ihangan\MoldovaCuatm\Models\Location;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;

/**
 * What happens when the Bureau publishes a new edition and the shipped dataset
 * is replaced. Rows are matched on the CUATM identifier, the one thing about a
 * locality that does not change.
 */
final class ImportUpdatesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->import();
    }

    #[Test]
    public function a_second_run_writes_nothing(): void
    {
        $before = Location::query()->orderBy('id')->pluck('updated_at', 'id')->all();

        $this->assertSame(
            ['created' => 0, 'renamed' => 0, 'updated' => 0, 'unchanged' => 1721],
            $this->countsFrom($this->reimport()),
        );

        // Nothing was written, so not a single timestamp moved.
        $this->assertEquals($before, Location::query()->orderBy('id')->pluck('updated_at', 'id')->all());
    }

    #[Test]
    public function a_renamed_locality_keeps_its_row(): void
    {
        $dobrogea = Location::query()->where('slug', 'dobrogea')->firstOrFail();
        $id = $dobrogea->id;

        // Rewind it to how an earlier edition would have spelled it: same CUATM
        // identifier, different name and slug.
        $dobrogea->forceFill([
            'slug' => 'dobrogea-editia-trecuta',
            'name' => ['ro' => 'Dobrogea din ediția trecută'],
        ])->saveQuietly();

        $output = $this->reimport();
        $counts = $this->countsFrom($output);

        $this->assertSame(1, $counts['renamed']);
        $this->assertSame(0, $counts['created'], 'a rename must not insert a second row');
        $this->assertStringContainsString('dobrogea-editia-trecuta -> dobrogea', $output);

        $renamed = Location::query()->where('slug', 'dobrogea')->firstOrFail();

        $this->assertSame($id, $renamed->id);
        $this->assertSame('Dobrogea', $renamed->getTranslation('name', 'ro'));
        $this->assertNull(Location::query()->where('slug', 'dobrogea-editia-trecuta')->first());
        $this->assertSame(1721, Location::query()->count());
    }

    #[Test]
    public function a_locality_the_classifier_dropped_is_reported_and_left_alone(): void
    {
        $ghost = $this->ghost();

        $output = $this->reimport();
        $counts = $this->countsFrom($output);

        $this->assertSame(1721, $counts['unchanged']);
        $this->assertStringContainsString('no longer in the classifier', $output);
        $this->assertStringContainsString('ghost-village (code 9999)', $output);

        // An application's own rows may point at it, so deleting is not ours to do.
        $this->assertNotNull($ghost->fresh());
        $this->assertSame(1722, Location::query()->count());
    }

    #[Test]
    public function an_installation_upgrading_from_1x_is_matched_by_slug(): void
    {
        $chisinau = Location::query()->where('slug', 'chisinau')->firstOrFail();
        $id = $chisinau->id;

        // 1.x kept the statistical code in the code column, so nothing matches on
        // the CUATM identifier and the slug has to carry the row over.
        $chisinau->forceFill(['code' => '0101000'])->saveQuietly();

        $counts = $this->countsFrom($this->reimport());

        $this->assertSame(0, $counts['created']);
        $this->assertSame(1, $counts['updated']);

        $chisinau->refresh();

        $this->assertSame($id, $chisinau->id);
        $this->assertSame('0100', $chisinau->code);
        $this->assertSame('0101000', $chisinau->statistic_code);
        $this->assertSame(1721, Location::query()->count());
    }

    #[Test]
    public function a_locality_added_by_the_new_edition_is_created(): void
    {
        Location::query()->where('slug', 'revaca')->delete();

        $counts = $this->countsFrom($this->reimport());

        $this->assertSame(1, $counts['created']);
        $this->assertNotNull(Location::query()->where('slug', 'revaca')->first());
        $this->assertSame(1721, Location::query()->count());
    }

    #[Test]
    public function each_run_reports_only_its_own_figures(): void
    {
        // The command is resolved from the container, so a second invocation in
        // the same process must not inherit the first one's counters.
        $this->ghost();

        $first = $this->countsFrom($this->reimport());
        $second = $this->countsFrom($this->reimport());

        $this->assertSame($first, $second);
        $this->assertSame(0, $first['created']);
    }

    private function reimport(): string
    {
        Artisan::call('cuatm:import');

        return Artisan::output();
    }

    /**
     * The figures off the summary table.
     *
     * @return array{created: int, renamed: int, updated: int, unchanged: int}
     */
    private function countsFrom(string $output): array
    {
        $counts = [];

        foreach (['created', 'renamed', 'updated', 'unchanged'] as $key) {
            $matched = preg_match('/\|\s*'.$key.'\s*\|\s*(\d+)\s*\|/', $output, $matches) === 1;

            $this->assertTrue($matched, "The summary has no {$key} row.");

            $counts[$key] = (int) $matches[1];
        }

        return [
            'created' => $counts['created'],
            'renamed' => $counts['renamed'],
            'updated' => $counts['updated'],
            'unchanged' => $counts['unchanged'],
        ];
    }

    private function ghost(): Location
    {
        return Location::query()->create([
            'code' => '9999',
            'statistic_code' => '9999999',
            'slug' => 'ghost-village',
            'name' => ['ro' => 'Ghost'],
            'type' => LocationType::Village,
            'sort_order' => 9999,
        ]);
    }
}
