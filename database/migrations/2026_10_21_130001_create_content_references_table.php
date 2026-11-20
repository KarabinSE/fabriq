<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_references', function (Blueprint $table) {
            $table->foreignId('definition_id')->constrained('i18n_definitions')->cascadeOnDelete();
            $table->string('resource_type', 32);
            $table->unsignedBigInteger('resource_id');
            $table->unique(['definition_id', 'resource_type', 'resource_id'], 'content_reference_unique');
            $table->index(['resource_type', 'resource_id'], 'content_reference_resource');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_references');
    }
};
