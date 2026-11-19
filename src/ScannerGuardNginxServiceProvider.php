<?php

declare(strict_types=1);

namespace JeffersonGoncalves\ScannerGuardNginx;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ScannerGuardNginxServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-scanner-guard-nginx')
            ->hasConfigFile('scanner-guard-nginx');
    }
}
