<?php

namespace Karabin\Fabriq\Console\Commands;

use Illuminate\Console\Command;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Models\ContentTemplate;
use Karabin\Fabriq\Models\Locale;

class FabriqDefaultSetup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fabriq:default-setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initializes fabriq with page root, main menu and footer menu';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->createStartPage();
        $this->createMainMenu();
        $this->createFooterMenu();
    }

    private function createStartPage()
    {
        $template = ContentTemplate::where('name', 'Landningssida')
            ->where('type', 'page')
            ->firstOrFail();
        $root = Fabriq::getModelClass('page')
            ->where('parent_id', null)
            ->firstOrFail();
        $page = Fabriq::getModelClass('page');
        $page->name = 'Start';
        $page->template_id = $template->id; // Landningssida
        $page->parent_id = $root->id; // root
        $page->save();

        $this->info('✅ Start page has been created successfully');
    }

    private function createMainMenu()
    {
        $this->createMenu('Huvudmeny', 'main_menu');
    }

    private function createFooterMenu()
    {
        $this->createMenu('Sidfotsmeny', 'footer_menu');
    }

    protected function createMenu(string $name, string $slug)
    {
        if (Fabriq::getModelClass('menu')->whereName($name)->first()) {
            $this->info("❌ {$name} already exists");

            return;
        }

        $menu = Fabriq::getModelClass('menu');
        $menu->name = $name;
        $menu->slug = $slug;
        $menu->save();

        Locale::enabled()->get()
            ->each(function ($locale) use ($menu) {
                $menuItem = Fabriq::getModelClass('menuItem');
                $menuItem->menu_id = $menu->id;
                $menuItem->locale = $locale->iso_code;
                $menuItem->save();
            });

        $this->info("✅ {$name} created successfully");
        $this->table(
            ['Name', 'Slug', 'id'],
            [collect($menu)->only(['id', 'name', 'slug'])->toArray()]
        );
    }
}
