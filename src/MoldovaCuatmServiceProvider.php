<?php

declare(strict_types=1);

namespace Ihangan\MoldovaCuatm;

use Ihangan\MoldovaCuatm\Commands\ImportCuatmCommand;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class MoldovaCuatmServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-moldova-cuatm')
            ->hasConfigFile('moldova-cuatm')
            ->hasMigration('create_cuatm_locations_table')
            ->hasCommand(ImportCuatmCommand::class)
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('ihangan/laravel-moldova-cuatm');
            });
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(Cuatm::class);
    }
}
