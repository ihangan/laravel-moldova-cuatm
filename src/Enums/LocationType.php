<?php

declare(strict_types=1);

namespace Ihangan\MoldovaCuatm\Enums;

enum LocationType: string
{
    case District = 'district';
    case Municipality = 'municipality';
    case AutonomousUnit = 'autonomous_unit';
    case TerritorialUnit = 'territorial_unit';
    case Sector = 'sector';
    case Town = 'town';
    case Village = 'village';

    /**
     * The type as a reader would say it: "raion" in Romanian, "district" in
     * English. Publish the files with the moldova-cuatm-translations tag to
     * override them.
     */
    public function label(?string $locale = null): string
    {
        $label = trans('moldova-cuatm::location-types.'.$this->value, locale: $locale);

        return is_string($label) ? $label : $this->value;
    }

    /**
     * Types that sit at the top of the hierarchy and never have a parent.
     *
     * @return list<self>
     */
    public static function roots(): array
    {
        return [self::District, self::Municipality, self::AutonomousUnit, self::TerritorialUnit];
    }
}
