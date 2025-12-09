<?php

namespace Laravel\Ranger\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ranger\RangerServiceProvider;
use Laravel\Surveyor\SurveyorServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            SurveyorServiceProvider::class,
            RangerServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../workbench/database/migrations');
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function defineRoutes($router): void
    {
        require __DIR__.'/../workbench/routes/web.php';
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:uz4B1RtFO57QGzbZX1kRYX9hIRB50+QzqFeg9zbFJlY=');

        $app->useAppPath(__DIR__.'/../workbench/app');
    }
}
