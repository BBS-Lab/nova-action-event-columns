<?php

declare(strict_types=1);

namespace BBSLab\NovaActionEventColumns\Tests;

use BBSLab\NovaActionEventColumns\Nova\ActionResource;
use BBSLab\NovaActionEventColumns\NovaActionEventColumnsServiceProvider;
use Illuminate\Foundation\Application;
use Laravel\Nova\NovaCoreServiceProvider;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use WithWorkbench;

    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            NovaCoreServiceProvider::class,
            NovaActionEventColumnsServiceProvider::class,
        ];
    }

    /**
     * App-side activation the README asks for: point Nova at the package's
     * ActionResource so both write paths flow through our column-filling model.
     *
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('nova.actions.resource', ActionResource::class);
    }
}
