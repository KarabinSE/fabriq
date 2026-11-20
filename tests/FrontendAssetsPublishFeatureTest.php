<?php

namespace Karabin\Fabriq\Tests;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class FrontendAssetsPublishFeatureTest extends AdminUserTestCase
{
    public function test_frontend_install_assets_publish_the_api_spa_runtime(): void
    {
        File::deleteDirectory(resource_path('fabriq/js'));

        Artisan::call('vendor:publish', [
            '--provider' => 'Karabin\Fabriq\FabriqCoreServiceProvider',
            '--tag' => 'fabriq-frontend-install-assets',
            '--force' => true,
        ]);

        $this->assertFileExists(resource_path('fabriq/js/fabriq.js'));
        $this->assertDirectoryExists(resource_path('fabriq/js/routes'));
        $this->assertDirectoryExists(resource_path('fabriq/js/icons'));
        $this->assertDirectoryExists(resource_path('fabriq/js/stores'));

    }

    public function test_frontend_update_assets_publish_the_api_spa_runtime(): void
    {
        File::deleteDirectory(resource_path('fabriq/js'));
        File::ensureDirectoryExists(resource_path('fabriq/js'));
        File::put(resource_path('fabriq/js/fabriq.js'), 'old fabriq runtime');
        File::put(resource_path('js/app.js'), 'old app entrypoint');

        Artisan::call('vendor:publish', [
            '--provider' => 'Karabin\Fabriq\FabriqCoreServiceProvider',
            '--tag' => 'fabriq-frontend-assets',
            '--force' => true,
        ]);

        $this->assertFileExists(resource_path('fabriq/js/fabriq.js'));
        $this->assertSame(
            File::get(__DIR__.'/../resources/fabriq/js/fabriq.js'),
            File::get(resource_path('fabriq/js/fabriq.js'))
        );
        $this->assertSame(
            File::get(__DIR__.'/../resources/js/app.js'),
            File::get(resource_path('js/app.js'))
        );
        $this->assertDirectoryExists(resource_path('fabriq/js/icons'));
        $this->assertDirectoryExists(resource_path('fabriq/js/stores'));

    }
}
