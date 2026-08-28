<?php

declare(strict_types=1);

namespace Ihangan\MoldovaCuatm\Commands;

use Ihangan\MoldovaCuatm\Enums\LocationType;
use Ihangan\MoldovaCuatm\Models\Location;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

/**
 * @phpstan-type CuatmRow array{
 *     cuatm_code: string,
 *     statistic_code: string,
 *     slug: string,
 *     type: string,
 *     parent_slug: string|null,
 *     name_ro: string,
 *     name_ru?: string|null,
 *     name_uk?: string|null,
 *     name_en?: string|null,
 *     lat: float|null,
 *     lng: float|null
 * }
 */
final class ImportCuatmCommand extends Command
{
    use ConfirmableTrait;

    protected $signature = 'cuatm:import
        {--fresh : Delete every existing row before importing}
        {--force : Force the operation to run when in production}';

    protected $description = 'Load the Moldovan CUATM localities into the cuatm_locations table.';

    /**
     * Rows already in the table, by their CUATM identifier and by their slug.
     *
     * @var array<string, int>
     */
    private array $idByCode = [];

    /** @var array<string, int> */
    private array $idBySlug = [];

    public function handle(): int
    {
        if ($this->option('fresh') && ! $this->clear()) {
            return self::FAILURE;
        }

        $rows = $this->rows();

        /** @var array<string, int> $idByCode */
        $idByCode = Location::query()->pluck('id', 'code')->all();
        $this->idByCode = $idByCode;

        /** @var array<string, int> $idBySlug */
        $idBySlug = Location::query()->pluck('id', 'slug')->all();
        $this->idBySlug = $idBySlug;

        /** @var list<int> $knownIds */
        $knownIds = Location::query()->pluck('id')->all();

        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        $tally = ['created' => 0, 'renamed' => 0, 'updated' => 0, 'unchanged' => 0];
        $renames = [];
        $touched = [];
        $sortOrder = 0;

        foreach ($rows as $row) {
            $location = $this->prepare($row, $this->resolveParentId($row['parent_slug']), $sortOrder++);

            $outcome = $this->outcomeFor($location);
            $tally[$outcome]++;

            if ($outcome === 'renamed') {
                $previous = $location->getOriginal('slug');

                $renames[] = [
                    'from' => is_string($previous) ? $previous : '?',
                    'to' => $location->slug,
                    'code' => $location->code,
                ];
            }

            $location->save();

            $touched[] = $location->id;
            $this->idByCode[$location->code] = $location->id;
            $this->idBySlug[$location->slug] = $location->id;

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->report($tally, $renames, array_values(array_diff($knownIds, $touched)));

        return self::SUCCESS;
    }

    /**
     * Wiping the table detaches every application row pointing at a location,
     * so it asks first when it is running in production.
     */
    private function clear(): bool
    {
        if (! $this->confirmToProceed('This deletes every row in the locations table.')) {
            return false;
        }

        Location::query()->delete();
        $this->warn('Cleared existing locations.');

        return true;
    }

    /**
     * The CUATM identifier is the stable one: a locality can be renamed, which
     * changes its name and its slug but never its identifier. Falling back to
     * the slug is what carries an installation over from 1.x, where the code
     * column still held the statistical code and nothing matches by identifier.
     *
     * @param  CuatmRow  $row
     */
    private function prepare(array $row, ?int $parentId, int $sortOrder): Location
    {
        $id = $this->idByCode[$row['cuatm_code']] ?? $this->idBySlug[$row['slug']] ?? null;

        $location = $id === null ? new Location : Location::query()->findOrNew($id);

        $location->fill([
            'parent_id' => $parentId,
            'code' => $row['cuatm_code'],
            'statistic_code' => $row['statistic_code'],
            'name' => $this->buildName($row),
            'slug' => $row['slug'],
            'type' => LocationType::from($row['type']),
            'lat' => $row['lat'],
            'lng' => $row['lng'],
            'sort_order' => $sortOrder,
        ]);

        return $location;
    }

    /**
     * @return 'created'|'renamed'|'updated'|'unchanged'
     */
    private function outcomeFor(Location $location): string
    {
        if (! $location->exists) {
            return 'created';
        }

        if ($location->isDirty('slug')) {
            return 'renamed';
        }

        return $location->isDirty() ? 'updated' : 'unchanged';
    }

    /**
     * @param  array{created: int, renamed: int, updated: int, unchanged: int}  $tally
     * @param  list<array{from: string, to: string, code: string}>  $renames
     * @param  list<int>  $staleIds  rows in the table that the dataset no longer has
     */
    private function report(array $tally, array $renames, array $staleIds): void
    {
        $this->table(
            ['', 'localities'],
            [
                ['created', $tally['created']],
                ['renamed', $tally['renamed']],
                ['updated', $tally['updated']],
                ['unchanged', $tally['unchanged']],
            ],
        );

        foreach ($renames as $rename) {
            $this->line(sprintf('  renamed: %s -> %s (code %s)', $rename['from'], $rename['to'], $rename['code']));
        }

        if ($staleIds === []) {
            return;
        }

        $stale = Location::query()
            ->whereKey($staleIds)
            ->orderBy('slug')
            ->get()
            ->map(static fn (Location $location): string => sprintf('%s (code %s)', $location->slug, $location->code));

        $this->newLine();
        $this->warn(sprintf(
            '%d row(s) are no longer in the classifier. They were left alone, in case your own tables point at them:',
            $stale->count(),
        ));
        $this->line('  '.$stale->implode(', '));
    }

    private function resolveParentId(?string $parentSlug): ?int
    {
        if ($parentSlug === null) {
            return null;
        }

        if (isset($this->idBySlug[$parentSlug])) {
            return $this->idBySlug[$parentSlug];
        }

        $parent = Location::query()->where('slug', $parentSlug)->firstOrFail();
        $this->idBySlug[$parent->slug] = $parent->id;

        return $parent->id;
    }

    /**
     * @param  CuatmRow  $row
     * @return array<string, string>
     */
    private function buildName(array $row): array
    {
        $name = ['ro' => $row['name_ro']];

        if (($row['name_ru'] ?? '') !== '') {
            $name['ru'] = (string) $row['name_ru'];
        }

        if (($row['name_uk'] ?? '') !== '') {
            $name['uk'] = (string) $row['name_uk'];
        }

        if (($row['name_en'] ?? '') !== '') {
            $name['en'] = (string) $row['name_en'];
        }

        return $name;
    }

    /**
     * @return list<CuatmRow>
     */
    private function rows(): array
    {
        $path = dirname(__DIR__, 2).'/database/data/cuatm.json';

        /** @var list<CuatmRow> $rows */
        $rows = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        return $rows;
    }
}
