<?php

namespace Karabin\Fabriq\Database\Seeders;

use Illuminate\Database\Seeder;
use Karabin\Fabriq\Models\BlockType;
use Karabin\Fabriq\Models\ContentTemplate;
use Karabin\Fabriq\Models\ContentTemplateField;

class PageTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $template = ContentTemplate::factory()->create([
            'name' => 'Landningssida',
            'slug' => 'startsida',
        ]);
        ContentTemplateField::factory()->create([
            'template_id' => $template->id,
            'name' => 'Meta-titel',
            'key' => 'meta_title',
            'group' => 'meta',
            'options' => ['classes' => 'col-span-5'],
            'type' => 'text',
            'sort_index' => 10,
            'translated' => true,
        ]);
        ContentTemplateField::factory()->create([
            'template_id' => $template->id,
            'name' => 'Meta-beskrivning',
            'key' => 'meta_description',
            'options' => ['classes' => 'col-span-5'],
            'group' => 'meta',
            'type' => 'text',
            'sort_index' => 20,
            'translated' => true,
        ]);
        ContentTemplateField::factory()->create([
            'template_id' => $template->id,
            'name' => 'Meta-bild',
            'key' => 'meta_og_image',
            'group' => 'meta',
            'options' => ['group' => 'meta_og_image', 'classes' => 'col-span-4'],
            'type' => 'image',
            'sort_index' => 30,
            'translated' => true,
        ]);
        ContentTemplateField::factory()->create([
            'template_id' => $template->id,
            'name' => 'Titel',
            'key' => 'page_title',
            'group' => 'main_content',
            'options' => ['classes' => 'hidden'],
            'type' => 'text',
            'sort_index' => 40,
            'translated' => true,
        ]);
        ContentTemplateField::factory()->create([
            'template_id' => $template->id,
            'name' => 'Rubrik',
            'key' => 'page_header',
            'group' => 'main_content',
            'options' => ['classes' => 'col-span-5'],
            'type' => 'text',
            'sort_index' => 40,
            'translated' => true,
        ]);
        ContentTemplateField::factory()->create([
            'template_id' => $template->id,
            'name' => 'Huvudbild',
            'key' => 'main_image',
            'group' => 'main_content',
            'options' => ['group' => 'main_image', 'classes' => 'col-span-4'],
            'type' => 'image',
            'sort_index' => 50,
            'translated' => true,
        ]);
        ContentTemplateField::factory()->create([
            'template_id' => $template->id,
            'name' => 'Innehåll',
            'key' => 'page_content',
            'group' => 'main_content',
            'type' => 'html',
            'sort_index' => 60,
            'translated' => true,
        ]);
        ContentTemplateField::factory()->create([
            'template_id' => $template->id,
            'translated' => true,
            'name' => 'Box',
            'key' => 'boxes',
            'repeater' => true,
            'group' => 'boxes',
            'type' => 'repeater',
            'sort_index' => 60,
        ]);

        BlockType::factory()->create([
            'name' => 'Demo-block',
            'component_name' => 'DemoBlock',
            'active' => 1,
            'type' => 'block',
            'has_children' => 1,
        ]);
    }
}
