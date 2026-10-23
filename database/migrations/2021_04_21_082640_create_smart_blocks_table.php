<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSmartBlocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('smart_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        $templateId = DB::table('revision_templates')->insertGetId([
            'name' => 'Smart Block',
            'slug' => 'smart_block',
            'type' => 'smart_block',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('revision_template_fields')->insert([
            'template_id' => $templateId,
            'translated' => true,
            'name' => 'Box',
            'key' => 'boxes',
            'repeater' => true,
            'group' => 'boxes',
            'type' => 'repeater',
            'sort_index' => 10,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('smart_blocks');
    }
}
