<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('block_references', function (Blueprint $table) {
            $table->foreignId('block_id')->constrained('blocks')->cascadeOnDelete();
            $table->string('resource_type', 32);
            $table->unsignedBigInteger('resource_id');
            $table->unique(['block_id', 'resource_type', 'resource_id'], 'block_reference_unique');
            $table->index(['resource_type', 'resource_id'], 'block_reference_resource');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('block_references');
    }
};
