<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddContactRevisionTemplate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $templateId = DB::table('revision_templates')->insertGetId([
            'name' => 'Kontakt',
            'slug' => 'contact',
            'type' => 'contact',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('revision_template_fields')->insert([
            [
                'template_id' => $templateId,
                'name' => 'Text',
                'key' => 'body',
                'type' => 'html',
                'translated' => true,
                'group' => 'contact',
                'repeater' => false,
                'sort_index' => 10,
            ],
            [
                'template_id' => $templateId,
                'name' => 'image',
                'key' => 'image',
                'type' => 'image',
                'translated' => false,
                'group' => 'contact',
                'repeater' => false,
                'sort_index' => 20,
            ],
            [
                'template_id' => $templateId,
                'name' => 'Roll',
                'key' => 'position',
                'type' => 'text',
                'translated' => true,
                'group' => 'contact',
                'repeater' => false,
                'sort_index' => 30,
            ],
        ]);

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('body');
        });
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
