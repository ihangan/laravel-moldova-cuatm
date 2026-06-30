<?php

declare(strict_types=1);

namespace Ihangan\MoldovaCuatm\Tests;

use Ihangan\MoldovaCuatm\MoldovaCuatmServiceProvider;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Translatable\Facades\Translatable;
use Spatie\Translatable\TranslatableServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // The shipped data only guarantees Romanian, so missing locales should
        // resolve back to it instead of returning an empty string.
        Translatable::fallback(fallbackLocale: 'ro', fallbackAny: true);
    }

    /**
     * @param  Application  $app
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            TranslatableServiceProvider::class,
            MoldovaCuatmServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        $migration = include __DIR__.'/../database/migrations/create_cuatm_locations_table.php.stub';
        $migration->up();
    }
}
