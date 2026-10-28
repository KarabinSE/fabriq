<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTranslatableRevisionTemplateFieldsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tableName = 'revision_template_fields';

        Schema::create($tableName, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('template_id');
            $table->foreign('template_id')->references('id')->on('revision_templates')->onDelete('cascade');
            $table->string('name');
            $table->string('key');
            $table->string('type');
            $table->string('group')->nullable();
            $table->boolean('repeater')->default(0);
            $table->boolean('translated')->default(0);
            $table->integer('sort_index')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('revision_template_fields');
    }
}
