<?php

declare(strict_types=1);

namespace RJP\Tiptap;

use RJP\Tiptap\Contracts\TiptapInterface;
use Spatie\LaravelPackageTools\{
    Package,
    PackageServiceProvider
};

class TiptapServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-tiptap')
            ->hasConfigFile();
    }

    public function packageRegistered(): void
    {
        $this->app->bind(TiptapInterface::class, Tiptap::class);
        $this->app->bind('tiptap', Tiptap::class);
    }
}
