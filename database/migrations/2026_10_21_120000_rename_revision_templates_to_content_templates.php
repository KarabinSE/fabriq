<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('revision_templates', 'content_templates');
        Schema::rename('revision_template_fields', 'content_template_fields');
    }

    public function down(): void
    {
        Schema::rename('content_template_fields', 'revision_template_fields');
        Schema::rename('content_templates', 'revision_templates');
    }
};
