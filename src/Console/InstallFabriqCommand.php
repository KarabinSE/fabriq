<?php

namespace Karabin\Fabriq\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RuntimeException;

class InstallFabriqCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fabriq:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Installs Fabriq CMS into the Laravel project';

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
        $this->info('Installing...');

        $this->call('vendor:publish', [
            '--provider' => 'Karabin\Fabriq\FabriqCoreServiceProvider',
            '--tag' => 'fabriq-frontend-install-assets',
            '--force' => true,
        ]);

        $this->call('vendor:publish', [
            '--provider' => 'Karabin\Fabriq\FabriqCoreServiceProvider',
            '--tag' => 'fabriq-views',
            '--force' => true,
        ]);

        $this->info('Front end assets has been installed');

        $this->info('Installing translations');
        $this->call('vendor:publish', [
            '--provider' => 'Karabin\Fabriq\FabriqCoreServiceProvider',
            '--tag' => 'fabriq-translations',
        ]);

        if ($this->call('vendor:publish', [
            '--provider' => 'Karabin\Fabriq\FabriqCoreServiceProvider',
            '--tag' => 'fabriq-public-images',
            '--force' => true,
        ]) !== self::SUCCESS) {
            return self::FAILURE;
        }

        $this->info('Installing config');
        if ($this->call('vendor:publish', [
            '--provider' => 'Karabin\Fabriq\FabriqCoreServiceProvider',
            '--tag' => 'config',
            '--force' => true,
        ]) !== self::SUCCESS) {
            return self::FAILURE;
        }

        $this->info('Copying assets to public directory');
        if (! File::copyDirectory(__DIR__.'/../../resources/fabriq/images', public_path('fabriq/images'))) {
            throw new RuntimeException('Unable to copy Fabriq images to the public directory.');
        }

        $this->info('Translations has been installed');

        $this->info('Migrating...');
        $this->call('migrate');

        $this->info('Creating page root');

        if (config('app.env') != 'testing') {
            $this->call('fabriq:create-page-root', [
                '--silent' => true,
            ]);

            $answer = $this->ask('Do you want install default menus and a start page?', 'yes');

            if ($answer === 'yes') {
                $this->call('fabriq:default-setup');
            }

            $userAnswer = $this->ask('Do you want to create a new user?', 'yes');

            if ($userAnswer === 'yes') {
                $this->call('fabriq:make-user');
                $this->call('fabriq:add-role-to-user');
            }

            $typescriptAnswer = $this->ask('Do you want to generate routes (typescript:transform)', 'yes');

            if ($typescriptAnswer === 'yes') {
                $this->call('typescript:transform');
            }

        }

        $this->output->title('Fabriq has been installed');
        $this->info('A new .example file has been published with Fabriq specific config options');

        return 0;
    }
}
