<?php

namespace Karabin\Fabriq\Tests;

use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class AdminUserTestCase extends Orchestra
{
    use LazilyRefreshDatabase;

    private static bool $publishedPackageAssets = false;

    public $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        Fabriq::routes(function ($router) {
            $router->all();
        });

        Fabriq::routes(function ($router) {
            $router->allWeb();
        }, [
            'middleware' => ['web'],
        ]);

        $this->setUpDatabase($this->app);
        $this->publishPackageAssetsOnce();

        $user = User::factory()->create([
            'name' => 'Albin N',
            'email' => 'albin@infab.io',
        ]);

        $this->user = $user;
        $this->actingAs($user);
    }

    public function tearDown(): void
    {
        $dir = storage_path('/app/public/__test');
        File::deleteDirectory($dir);

        parent::tearDown();
    }

    public function setUpDatabase($app)
    {
        $this->loadMigrationsFrom(realpath(__DIR__.'/../database/migrations'));
    }

    private function publishPackageAssetsOnce(): void
    {
        if (self::$publishedPackageAssets) {
            return;
        }

        Artisan::call('vendor:publish', [
            '--provider' => 'Karabin\Fabriq\FabriqCoreServiceProvider',
            '--tag' => 'fabriq-frontend-install-assets',
            '--force' => true,
        ]);

        Artisan::call('vendor:publish', [
            '--provider' => 'Karabin\Fabriq\FabriqCoreServiceProvider',
            '--tag' => 'fabriq-views',
            '--force' => true,
        ]);

        Artisan::call('vendor:publish', [
            '--provider' => 'Karabin\Fabriq\FabriqCoreServiceProvider',
            '--tag' => 'fabriq-translations',
        ]);

        self::$publishedPackageAssets = true;
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('cache.default', 'array');
        $app['config']->set('queue.default', 'sync');
        $app['config']->set('hashing.bcrypt.rounds', 4);
        $app['config']->set('fabriq.webhooks.enabled', false);
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('filesystems.disks.__test', [
            'driver' => 'local',
            'root' => storage_path('app/public/__test'),
            'url' => env('APP_URL').'/storage/__test',
            'visibility' => 'public',
        ]);

        $app['config']->set('fabriq.models.user', \Karabin\Fabriq\Models\User::class);
    }

    protected function getPackageProviders($app)
    {
        return [
            \Karabin\Fabriq\FabriqCoreServiceProvider::class,
            \Karabin\Fabriq\FortifyServiceProvider::class,
        ];
    }
}
