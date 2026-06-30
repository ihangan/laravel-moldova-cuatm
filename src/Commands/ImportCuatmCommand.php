<?php

declare(strict_types=1);

namespace Ihangan\MoldovaCuatm\Commands;

use Ihangan\MoldovaCuatm\Enums\LocationType;
use Ihangan\MoldovaCuatm\Models\Location;
use Illuminate\Console\Command;

/**
 * @phpstan-type CuatmRow array{
 *     cuatm_code: string,
 *     slug: string,
 *     type: string,
 *     parent_slug: string|null,
 *     name_ro: string,
 *     name_ru?: string|null,
 *     name_uk?: string|null,
 *     lat: float|null,
 *     lng: float|null
 * }
 */
final class ImportCuatmCommand extends Command
{
    protected $signature = 'cuatm:import {--fresh : Delete existing rows before importing}';

    protected $description = 'Load the Moldovan CUATM localities into the cuatm_locations table.';

    /**
     * Names for the larger cities, sectors and special regions are curated by
     * hand so they read correctly in all four locales (the open data only gives
     * reliable Romanian, plus Wikidata exonyms for Russian and Ukrainian).
     *
     * @var array<string, array{ro: string, ru: string, uk: string, en: string}>
     */
    private const CURATED_NAMES = [
        'chisinau' => ['ro' => 'Chișinău', 'ru' => 'Кишинёв', 'uk' => 'Кишинів', 'en' => 'Chisinau'],
        'balti' => ['ro' => 'Bălți', 'ru' => 'Бельцы', 'uk' => 'Бєльці', 'en' => 'Balti'],
        'bender' => ['ro' => 'Bender', 'ru' => 'Бендеры', 'uk' => 'Бендери', 'en' => 'Bender'],
        'comrat' => ['ro' => 'Comrat', 'ru' => 'Комрат', 'uk' => 'Комрат', 'en' => 'Comrat'],
        'tiraspol' => ['ro' => 'Tiraspol', 'ru' => 'Тирасполь', 'uk' => 'Тирасполь', 'en' => 'Tiraspol'],
        'gagauzia' => ['ro' => 'Găgăuzia', 'ru' => 'Гагаузия', 'uk' => 'Гагаузія', 'en' => 'Gagauzia'],
        'stinga-nistrului' => ['ro' => 'Stînga Nistrului', 'ru' => 'Приднестровье', 'uk' => 'Придністров’я', 'en' => 'Transnistria'],

        'botanica' => ['ro' => 'Botanica', 'ru' => 'Ботаника', 'uk' => 'Ботаніка', 'en' => 'Botanica'],
        'buiucani' => ['ro' => 'Buiucani', 'ru' => 'Буюканы', 'uk' => 'Буюкани', 'en' => 'Buiucani'],
        'centru' => ['ro' => 'Centru', 'ru' => 'Центр', 'uk' => 'Центр', 'en' => 'Centru'],
        'ciocana' => ['ro' => 'Ciocana', 'ru' => 'Чеканы', 'uk' => 'Чокана', 'en' => 'Ciocana'],
        'riscani' => ['ro' => 'Râșcani', 'ru' => 'Рышкановка', 'uk' => 'Ришкани', 'en' => 'Riscani'],

        'cahul' => ['ro' => 'Cahul', 'ru' => 'Кагул', 'uk' => 'Кагул', 'en' => 'Cahul'],
        'ungheni' => ['ro' => 'Ungheni', 'ru' => 'Унгены', 'uk' => 'Унгени', 'en' => 'Ungheni'],
        'orhei' => ['ro' => 'Orhei', 'ru' => 'Орхей', 'uk' => 'Оргей', 'en' => 'Orhei'],
        'soroca' => ['ro' => 'Soroca', 'ru' => 'Сороки', 'uk' => 'Сороки', 'en' => 'Soroca'],
        'edinet' => ['ro' => 'Edineț', 'ru' => 'Единцы', 'uk' => 'Єдинці', 'en' => 'Edinet'],
        'hincesti' => ['ro' => 'Hîncești', 'ru' => 'Хынчешты', 'uk' => 'Хинчешти', 'en' => 'Hincesti'],
        'causeni' => ['ro' => 'Căușeni', 'ru' => 'Каушаны', 'uk' => 'Каушани', 'en' => 'Causeni'],
        'anenii-noi' => ['ro' => 'Anenii Noi', 'ru' => 'Анений Ной', 'uk' => 'Аненій Ной', 'en' => 'Anenii Noi'],
        'straseni' => ['ro' => 'Strășeni', 'ru' => 'Страшены', 'uk' => 'Страшени', 'en' => 'Straseni'],
        'ialoveni' => ['ro' => 'Ialoveni', 'ru' => 'Яловены', 'uk' => 'Яловени', 'en' => 'Ialoveni'],
    ];

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
        if (isset(self::CURATED_NAMES[$row['slug']])) {
            return self::CURATED_NAMES[$row['slug']];
        }

        $name = ['ro' => $row['name_ro']];

        if (($row['name_ru'] ?? '') !== '') {
            $name['ru'] = (string) $row['name_ru'];
        }

        if (($row['name_uk'] ?? '') !== '') {
            $name['uk'] = (string) $row['name_uk'];
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
