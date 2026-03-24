<?php

namespace Karabin\Fabriq\Tests;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class FrontendAssetsPublishFeatureTest extends AdminUserTestCase
{
    public function test_frontend_install_assets_publish_the_api_spa_runtime(): void
    {
        File::deleteDirectory(resource_path('js'));

        Artisan::call('vendor:publish', [
            '--provider' => 'Karabin\Fabriq\FabriqCoreServiceProvider',
            '--tag' => 'fabriq-frontend-install-assets',
            '--force' => true,
        ]);

        $this->assertFileExists(resource_path('js/fabriq.js'));
        $this->assertDirectoryExists(resource_path('js/routes'));
        $this->assertDirectoryExists(resource_path('js/icons'));
        $this->assertDirectoryExists(resource_path('js/store'));

    }

    public function test_frontend_update_assets_publish_the_api_spa_runtime(): void
    {
        File::deleteDirectory(resource_path('js'));

        Artisan::call('vendor:publish', [
            '--provider' => 'Karabin\Fabriq\FabriqCoreServiceProvider',
            '--tag' => 'fabriq-frontend-assets',
            '--force' => true,
        ]);

        $this->assertFileExists(resource_path('js/fabriq.js'));
        $this->assertDirectoryExists(resource_path('js/icons'));
        $this->assertDirectoryExists(resource_path('js/store'));

    }
}
