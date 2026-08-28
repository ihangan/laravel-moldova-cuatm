<?php

declare(strict_types=1);

namespace Ihangan\MoldovaCuatm\Commands;

use Ihangan\MoldovaCuatm\Enums\LocationType;
use Ihangan\MoldovaCuatm\Models\Location;
use Illuminate\Console\Command;

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
    protected $signature = 'cuatm:import {--fresh : Delete existing rows before importing}';

    protected $description = 'Load the Moldovan CUATM localities into the cuatm_locations table.';

    public function handle(): int
    {
        if ($this->option('fresh')) {
            Location::query()->delete();
            $this->warn('Cleared existing locations.');
        }

        $rows = $this->rows();

        /** @var array<string, int> $slugToId */
        $slugToId = Location::query()->pluck('id', 'slug')->all();

        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        $sortOrder = 0;
        foreach ($rows as $row) {
            $parentId = $this->resolveParentId($row['parent_slug'], $slugToId);
            $location = $this->upsert($row, $parentId, $sortOrder++);
            $slugToId[$location->slug] = $location->id;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info(sprintf('Imported %d localities.', count($rows)));

        return self::SUCCESS;
    }

    /**
     * @param  array<string, int>  $slugToId
     */
    private function resolveParentId(?string $parentSlug, array &$slugToId): ?int
    {
        if ($parentSlug === null) {
            return null;
        }

        if (isset($slugToId[$parentSlug])) {
            return $slugToId[$parentSlug];
        }

        $parent = Location::query()->where('slug', $parentSlug)->firstOrFail();
        $slugToId[$parent->slug] = $parent->id;

        return $parent->id;
    }

    /**
     * @param  CuatmRow  $row
     */
    private function upsert(array $row, ?int $parentId, int $sortOrder): Location
    {
        return Location::query()->updateOrCreate(
            ['slug' => $row['slug']],
            [
                'parent_id' => $parentId,
                'code' => $row['cuatm_code'],
                'statistic_code' => $row['statistic_code'],
                'name' => $this->buildName($row),
                'type' => LocationType::from($row['type']),
                'lat' => $row['lat'],
                'lng' => $row['lng'],
                'sort_order' => $sortOrder,
            ],
        );
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
