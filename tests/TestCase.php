<?php

namespace Karabin\Fabriq\Tests;

use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    public function setUp(): void
    {
        parent::setUp();

        // Pass::routes(function ($registrar) { $registrar->forAuthorization(); });

        $this->withoutVite();
        $this->loadLaravelMigrations(['--database' => 'testing']);
        $this->setUpDatabase($this->app);
    }

    public function setUpDatabase($app)
    {
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('cache.default', 'array');
        $app['config']->set('queue.default', 'sync');
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
