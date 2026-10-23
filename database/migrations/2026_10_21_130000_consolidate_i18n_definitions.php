<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureDefinitionsCanBeConsolidated();
        $this->ensureDefinitionsHaveNoMedia();

        Schema::table('i18n_definitions', function (Blueprint $table) {
            $table->after('content', function ($table) {
                $table->string('model_type')->nullable();
                $table->unsignedBigInteger('model_id')->nullable();
                $table->string('field_key')->nullable();
            });
        });

        $definitions = DB::table('i18n_definitions as definitions')
            ->join('i18n_terms as terms', 'terms.id', '=', 'definitions.term_id')
            ->select([
                'definitions.id as definition_id',
                'terms.model_type',
                'terms.model_id',
                'terms.field_key',
            ])
            ->get();

        foreach ($definitions as $definition) {
            DB::table('i18n_definitions')
                ->where('id', $definition->definition_id)
                ->update([
                    'model_type' => $definition->model_type,
                    'model_id' => $definition->model_id,
                    'field_key' => $definition->field_key,
                ]);
        }

        Schema::table('i18n_definitions', function (Blueprint $table) {
            $table->unique(
                ['model_type', 'model_id', 'field_key'],
                'i18n_definitions_model_field_unique'
            );
            $table->dropForeign(['term_id']);
            $table->dropIndex('i18n_definitions_term_id_index');
            $table->dropColumn('term_id');
        });

        Schema::table('i18n_definitions', function (Blueprint $table) {
            $table->dropColumn('approved');
        });

        Schema::drop('i18n_terms');
    }

    public function down(): void
    {
        throw new RuntimeException('Consolidated i18n definitions cannot be split back into terms.');
    }

    private function ensureDefinitionsCanBeConsolidated(): void
    {
        $invalidDefinitions = DB::table('i18n_definitions as definitions')
            ->leftJoin('i18n_terms as terms', 'terms.id', '=', 'definitions.term_id')
            ->where(function ($query) {
                $query->whereNull('terms.id')
                    ->orWhereNull('terms.model_type')
                    ->orWhere('terms.model_type', '')
                    ->orWhereNull('terms.model_id')
                    ->orWhereNull('terms.field_key')
                    ->orWhere('terms.field_key', '');
            })
            ->count();

        if ($invalidDefinitions > 0) {
            throw new RuntimeException(
                "Cannot consolidate i18n tables: {$invalidDefinitions} definition(s) have no valid model and field identity."
            );
        }

        $duplicateDefinitions = DB::table('i18n_definitions as definitions')
            ->join('i18n_terms as terms', 'terms.id', '=', 'definitions.term_id')
            ->select(['terms.model_type', 'terms.model_id', 'terms.field_key'])
            ->groupBy('terms.model_type', 'terms.model_id', 'terms.field_key')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplicateDefinitions) {
            throw new RuntimeException(
                'Cannot consolidate i18n tables: multiple definitions exist for the same model field.'
            );
        }
    }

    private function ensureDefinitionsHaveNoMedia(): void
    {
        if (! Schema::hasTable('media')) {
            return;
        }

        $definitionTypes = [
            'fabriq_i18nDefinition',
            'Karabin\\Fabriq\\Models\\I18nDefinition',
            'Ikoncept\\Fabriq\\Models\\I18nDefinition',
        ];

        $mediaCount = DB::table('media')
            ->whereIn('model_type', $definitionTypes)
            ->count();

        if ($mediaCount > 0) {
            throw new RuntimeException(
                "Cannot remove Media Library support from i18n definitions: {$mediaCount} media item(s) are attached."
            );
        }
    }
};
