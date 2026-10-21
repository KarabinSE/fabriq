<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddLandingPageTemplateToTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! DB::table('revision_templates')->where('type', 'page')->first()) {
            $templateId = DB::table('revision_templates')->insertGetId([
                'name' => 'Landningssida',
                'slug' => 'startsida',
                'type' => 'page',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $fields = [
                ['template_id' => $templateId, 'name' => 'Meta-titel', 'key' => 'meta_title', 'group' => 'meta', 'options' => json_encode(['classes' => 'col-span-5']), 'type' => 'text', 'sort_index' => 10, 'translated' => true],
                ['template_id' => $templateId, 'name' => 'Meta-beskrivning', 'key' => 'meta_description', 'options' => json_encode(['classes' => 'col-span-5']), 'group' => 'meta', 'type' => 'text', 'sort_index' => 20, 'translated' => true],
                ['template_id' => $templateId, 'name' => 'Meta-bild', 'key' => 'meta_og_image', 'group' => 'meta', 'options' => json_encode(['group' => 'meta_og_image', 'classes' => 'col-span-4']), 'type' => 'image', 'sort_index' => 30, 'translated' => true],
                ['template_id' => $templateId, 'name' => 'Titel', 'key' => 'page_title', 'group' => 'main_content', 'options' => json_encode(['classes' => 'hidden']), 'type' => 'text', 'sort_index' => 40, 'translated' => true],
                ['template_id' => $templateId, 'name' => 'Rubrik', 'key' => 'page_header', 'group' => 'main_content', 'options' => json_encode(['classes' => 'col-span-5']), 'type' => 'text', 'sort_index' => 40, 'translated' => true],
                ['template_id' => $templateId, 'name' => 'Huvudbild', 'key' => 'main_image', 'group' => 'main_content', 'options' => json_encode(['group' => 'main_image', 'classes' => 'col-span-4']), 'type' => 'image', 'sort_index' => 50, 'translated' => true],
                ['template_id' => $templateId, 'name' => 'Innehåll', 'key' => 'page_content', 'group' => 'main_content', 'type' => 'html', 'sort_index' => 60, 'translated' => true],
                ['template_id' => $templateId, 'name' => 'Box', 'key' => 'boxes', 'repeater' => true, 'group' => 'boxes', 'type' => 'repeater', 'sort_index' => 60, 'translated' => true],
            ];

            $defaults = [
                'template_id' => $templateId,
                'group' => null,
                'options' => null,
                'repeater' => false,
                'translated' => false,
                'sort_index' => 0,
            ];

            DB::table('revision_template_fields')->insert(array_map(
                fn (array $field) => array_merge($defaults, $field),
                $fields
            ));

            DB::table('block_types')->insert([
                'name' => 'Demo-block',
                'component_name' => 'DemoBlock',
                'active' => 1,
                'type' => 'block',
                'has_children' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {}
}
