<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $templateId = DB::table('revision_templates')
            ->where('slug', 'contact')
            ->value('id');

        if (! $templateId) {
            return;
        }

        DB::table('revision_template_fields')->insert([
            'template_id' => $templateId,
            'name' => 'Språk',
            'key' => 'enabled_locales',
            'group' => 'contact',
            'options' => json_encode([]),
            'type' => 'text',
            'sort_index' => 40,
            'translated' => false,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('revision_template_fields')
            ->where('key', 'enabled_locales')
            ->delete();
    }
};
