<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddArticleRevisionTemplate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        $templateId = DB::table('revision_templates')->insertGetId([
            'name' => 'Nyhet',
            'slug' => 'article',
            'type' => 'article',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('revision_template_fields')->insert([
            [
                'template_id' => $templateId,
                'name' => 'Titel',
                'key' => 'title',
                'type' => 'text',
                'translated' => true,
                'group' => 'article',
                'repeater' => false,
                'sort_index' => 10,
            ],
            [
                'template_id' => $templateId,
                'name' => 'image',
                'key' => 'image',
                'type' => 'image',
                'translated' => false,
                'group' => 'article',
                'repeater' => false,
                'sort_index' => 20,
            ],
            [
                'template_id' => $templateId,
                'name' => 'Ingress',
                'key' => 'preamble',
                'type' => 'textarea',
                'translated' => true,
                'group' => 'article',
                'repeater' => false,
                'sort_index' => 30,
            ],
            [
                'template_id' => $templateId,
                'name' => 'Innehåll',
                'key' => 'body',
                'type' => 'html',
                'translated' => true,
                'group' => 'article',
                'repeater' => false,
                'sort_index' => 40,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
