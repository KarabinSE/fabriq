<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('global_sections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('handle')->unique();
            $table->foreignId('template_id')->unique()->constrained('content_templates');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('global_sections');
    }
};
