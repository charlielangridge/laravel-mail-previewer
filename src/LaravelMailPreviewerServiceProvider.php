<?php

namespace Charlielangridge\LaravelMailPreviewer;

use Charlielangridge\LaravelMailPreviewer\Commands\LaravelMailPreviewerCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelMailPreviewerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-mail-previewer')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_mail_previewer_table')
            ->hasCommand(LaravelMailPreviewerCommand::class);
    }
}
