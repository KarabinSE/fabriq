<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('slugs')) {
            foreach (DB::table('i18n_terms')->whereNotNull('field_key')->get(['key', 'field_key']) as $term) {
                DB::table('slugs')
                    ->where('source_key', $term->key)
                    ->update(['source_key' => $term->field_key]);
            }
        }

        Schema::table('i18n_terms', function (Blueprint $table) {
            $table->dropUnique(['key']);
            $table->dropIndex(['key']);
            $table->dropColumn(['key', 'description', 'group']);
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Legacy i18n term columns and their values cannot be restored.');
    }
};
