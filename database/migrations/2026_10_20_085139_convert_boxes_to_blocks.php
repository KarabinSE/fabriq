<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Models\Block;
use Karabin\Fabriq\Models\Page;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (app()->environment() === 'testing') {
            return;
        }
        $locales = Fabriq::getModelClass('locale')->cachedLocales();
        foreach ($locales as $locale) {
            App::setLocale($locale->iso_code);
            $pages = Page::all()->each(function ($page) use ($locale) {
                $boxTerm = DB::table('i18n_terms')->where('field_key', 'boxes')
                    ->where('model_id', $page->id)
                    ->where('model_type', 'fabriq_page')
                    ->first();
                if (! $boxTerm) {
                    return;
                }
                $definition = DB::table('i18n_definitions')->where('term_id', $boxTerm->id)
                    ->where('locale', $locale->iso_code)
                    ->first();

                $content['boxes'] = json_decode($definition->content, true);

                if (isset($content['boxes'])) {
                    foreach ($content['boxes'] as $box) {
                        $children = null;
                        if (isset($box['children']) && count($box['children'])) {
                            $children = $box['children'];
                        }

                        unset($box['children']);
                        $blockTypeId = $box['block_type']['id'];
                        unset($box['block_type']);

                        $createdBlock = Block::updateOrCreate([
                            'uuid' => 'x',
                        ], [
                            'name' => $box['name'] ?? 'Block',
                            'locale' => $locale->iso_code,
                            'content' => $box,
                            'model_id' => $page->id,
                            'model_type' => $page->getMorphClass(),
                            'revision' => $page->revision,
                            'block_type_id' => $blockTypeId,
                        ]);

                        if (! is_countable($children)) {
                            continue;
                        }
                        foreach ($children as $child) {
                            Block::updateOrCreate([
                                'uuid' => 'x',
                            ], [
                                'name' => $child['name'] ?? 'Block',
                                'content' => $child,
                                'parent_id' => $createdBlock->id,
                                'model_id' => $page->id,
                                'locale' => $locale->iso_code,
                                'model_type' => $page->getMorphClass(),
                                'revision' => $page->revision,
                                'block_type_id' => $blockTypeId,
                            ]);
                        }

                    }
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blocks', function (Blueprint $table) {
            //
        });
    }
};
