<?php

declare(strict_types=1);

namespace Ihangan\MoldovaCuatm\Tests;

use Ihangan\MoldovaCuatm\MoldovaCuatmServiceProvider;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Application;
use Illuminate\Testing\PendingCommand;
use Livewire\LivewireServiceProvider;
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
            LivewireServiceProvider::class,
            TranslatableServiceProvider::class,
            MoldovaCuatmServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        // Livewire signs its payloads, so the test app needs a key.
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));

        $app['config']->set('view.paths', [
            __DIR__.'/Fixtures/views',
            ...(array) $app['config']->get('view.paths', []),
        ]);

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

        if (! $migration instanceof Migration || ! method_exists($migration, 'up')) {
            self::fail('The migration stub did not return a runnable migration.');
        }

        $migration->up();
    }

    /**
     * Run the importer and assert it finished cleanly.
     */
    protected function import(bool $fresh = false): void
    {
        $command = $this->artisan('cuatm:import', $fresh ? ['--fresh' => true] : []);

        if (! $command instanceof PendingCommand) {
            self::fail('artisan() did not return a pending command.');
        }

        $command->assertSuccessful();
    }
}
