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
        Schema::create('previews', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->nullableMorphs('model', 'preview_model');
            $table->json('content');
            $table->unique(['model_type', 'model_id'], 'previews_unique');
            $table->index(['model_type', 'model_id'], 'previews_lookup_idx');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('previews');
    }
};
