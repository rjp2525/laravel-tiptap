<?php

declare(strict_types=1);

namespace RJP\Tiptap\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use RJP\Tiptap\Facades\Tiptap;
use RJP\Tiptap\TiptapServiceProvider;
use Tiptap\Extensions\StarterKit;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('tiptap.extensions', [
            StarterKit::class => [],
        ]);
    }

    protected function getPackageProviders($app): array
    {
        return [
            TiptapServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Tiptap' => Tiptap::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('cache.default', 'array');
        $app['config']->set('tiptap.cache.enabled', false);
    }
}
