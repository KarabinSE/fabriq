<?php

namespace Karabin\Fabriq\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RuntimeException;

class UpdateFabriqCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fabriq:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates Fabriq CMS';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Updating front end assets');
        $this->info('Checking if working directory is clean');
        $result = exec('git status --short');

        if ((bool) $result) {
            $ok = $this->confirm('Working directory not clean, continue anyways?', true);
            if (! $ok) {
                $this->info('Okay, exiting');

                return 0;
            }
        }

        if (! File::exists(resource_path('fabriq/js/fabriq.js'))) {
            $ok = $this->confirm(
                'Fabriq v3 structure detected. Migrate to v4? The current resources directory will be moved to resources_backup.',
                false
            );
            if (! $ok) {
                $this->info('Migration cancelled. No update was performed.');

                return 0;
            }

            if (! $this->createResourcesBackup()) {
                return self::FAILURE;
            }
        }

        if ($this->call('vendor:publish', [
            '--provider' => 'Karabin\Fabriq\FabriqCoreServiceProvider',
            '--tag' => 'fabriq-frontend-assets',
            '--force' => true,
        ]) !== self::SUCCESS) {
            return self::FAILURE;
        }

        $this->publishMissingApplicationEntrypoints();

        if ($this->call('vendor:publish', [
            '--provider' => 'Karabin\Fabriq\FabriqCoreServiceProvider',
            '--tag' => 'fabriq-views',
            '--force' => true,
        ]) !== self::SUCCESS) {
            return self::FAILURE;
        }

        if ($this->call('vendor:publish', [
            '--provider' => 'Karabin\Fabriq\FabriqCoreServiceProvider',
            '--tag' => 'fabriq-images',
            '--force' => true,
        ]) !== self::SUCCESS) {
            return self::FAILURE;
        }

        if ($this->call('vendor:publish', [
            '--provider' => 'Karabin\Fabriq\FabriqCoreServiceProvider',
            '--tag' => 'fabriq-public-images',
            '--force' => true,
        ]) !== self::SUCCESS) {
            return self::FAILURE;
        }

        $this->info('Copying assets to public directory');
        if (! File::copyDirectory(__DIR__.'/../../resources/fabriq/images', public_path('fabriq/images'))) {
            throw new RuntimeException('Unable to copy Fabriq images to the public directory.');
        }

        $this->info('Front end assets has been installed');

        $this->info('Migrating...');
        $this->call('migrate');

        $this->info('Fabriq has been updated');

        return 0;
    }

    private function createResourcesBackup(): bool
    {
        $resourcesPath = resource_path();
        $backupPath = base_path('resources_backup');

        if (File::exists($backupPath)) {
            $this->error("Cannot migrate: {$backupPath} already exists. Move or rename it before retrying.");

            return false;
        }

        if (File::exists($resourcesPath) && ! File::moveDirectory($resourcesPath, $backupPath)) {
            $this->error("Unable to move {$resourcesPath} to {$backupPath}.");

            return false;
        }

        File::ensureDirectoryExists($resourcesPath);
        if (! File::isDirectory($resourcesPath)) {
            $this->error("Unable to create the new resources directory: {$resourcesPath}.");

            return false;
        }

        if (File::exists($backupPath)) {
            $this->info("Existing resources were preserved in {$backupPath}.");
        }

        return true;
    }

    private function publishMissingApplicationEntrypoints(): void
    {
        $entrypoints = [
            __DIR__.'/../../resources/js/app.js' => resource_path('js/app.js'),
            __DIR__.'/../../resources/js/routes/routes.js' => resource_path('js/routes/routes.js'),
            __DIR__.'/../../resources/js/routes/sidebar-items.js' => resource_path('js/routes/sidebar-items.js'),
            __DIR__.'/../../resources/css/app.css' => resource_path('css/app.css'),
        ];

        foreach ($entrypoints as $source => $destination) {
            if (File::exists($destination)) {
                continue;
            }

            if (! File::exists($source)) {
                throw new RuntimeException("Fabriq application entrypoint source does not exist: {$source}");
            }

            File::ensureDirectoryExists(dirname($destination));

            if (! File::copy($source, $destination)) {
                throw new RuntimeException("Unable to install Fabriq application entrypoint: {$destination}");
            }

            $this->info('Added '.$destination);
        }
    }
}
