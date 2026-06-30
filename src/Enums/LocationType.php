<?php

declare(strict_types=1);

namespace Ihangan\MoldovaCuatm\Enums;

enum LocationType: string
{
    case Raion = 'raion';
    case Municipality = 'municipality';
    case AutonomousUnit = 'autonomous_unit';
    case TerritorialUnit = 'territorial_unit';
    case City = 'city';
    case Sector = 'sector';
    case Town = 'town';
    case Village = 'village';

    /**
     * Types that sit at the top of the hierarchy and never have a parent.
     *
     * @return list<self>
     */
    public static function roots(): array
    {
        return [self::Raion, self::Municipality, self::AutonomousUnit, self::TerritorialUnit];
    }
}
