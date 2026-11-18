<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        //
        $prefix = '';
        $tableName = $prefix.'i18n_terms';
        if (! Schema::hasTable($tableName)) {
            return;
        }
        DB::table($tableName)->get()->each(function ($item) use ($tableName) {
            $fieldKey = Str::afterLast($item->key, '-');
            DB::table($tableName)
                ->where(['id' => $item->id])
                ->whereNull('field_key')
                ->update(['field_key' => $fieldKey]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
