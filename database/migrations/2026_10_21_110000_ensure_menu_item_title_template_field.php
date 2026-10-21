<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $templateId = DB::table('revision_templates')
            ->where('slug', 'menu-item')
            ->value('id');

        if ($templateId === null) {
            $templateId = DB::table('revision_templates')->insertGetId([
                'name' => 'Menu item',
                'slug' => 'menu-item',
                'type' => 'menu_item',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $titleExists = DB::table('revision_template_fields')
            ->where('template_id', $templateId)
            ->where('key', 'title')
            ->exists();

        if ($titleExists) {
            return;
        }

        DB::table('revision_template_fields')->insert([
            'template_id' => $templateId,
            'name' => 'Title',
            'key' => 'title',
            'type' => 'text',
            'group' => 'menu',
            'repeater' => false,
            'translated' => true,
            'sort_index' => 10,
            'options' => null,
        ]);
    }

    public function down(): void {}
};
