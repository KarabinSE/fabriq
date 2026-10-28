<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTranslatableRevisionMetaTable extends Migration
{
    public function up()
    {
        $tableName = 'revision_meta';

        Schema::create($tableName, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->nullableMorphs('model', 'model');
            // $table->unsignedBigInteger('model_id');
            $table->integer('model_version')->nullable();
            $table->string('meta_key');
            $table->text('meta_value')->nullable();
            $table->nullableTimestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('revision_meta');
    }
}
