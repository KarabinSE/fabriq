<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('blocks', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->string('name');
            $table->string('locale')->default('sv');
            $table->longText('content')->nullable();
            $table->boolean('hidden')->default(false);
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->foreign('parent_id')->references('id')->on('blocks')->onDelete('cascade');
            $table->unsignedBigInteger('revision')->default(0);
            $table->morphs('model', 'block_model');
            $table->unsignedBigInteger('block_type_id')->nullable();
            $table->foreign('block_type_id')->references('id')->on('block_types')->onDelete('set null');
            $table->unsignedBigInteger('sortindex')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blocks');
    }
};
