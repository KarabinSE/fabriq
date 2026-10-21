<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTranslatableRevisionTemplatesTable extends Migration
{
    public function up()
    {
        $tableName = 'revision_templates';

        Schema::create($tableName, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('slug');
            $table->nullableTimestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('revision_templates');
    }
}
