<?php

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $fieldKeys = DB::table('revision_template_fields')
            ->pluck('key')
            ->filter()
            ->unique()
            ->sortBy(fn ($key) => strlen((string) $key), descending: true)
            ->values()
            ->all();

        $terms = DB::table('i18n_terms')->get();
        $definitionsByTermId = DB::table('i18n_definitions')
            ->get()
            ->groupBy('term_id');

        $currentVersions = [];
        $groups = [];
        $standaloneTerms = [];

        foreach ($terms as $term) {
            $fieldKey = $term->field_key ?: $this->inferFieldKey((string) $term->key, $fieldKeys);

            if ($term->model_id && $term->model_type && $fieldKey) {
                $modelKey = $term->model_type.'|'.$term->model_id;
                $currentVersions[$modelKey] ??= $this->currentVersion($term->model_type, (int) $term->model_id);
                $groupKey = $modelKey.'|'.$fieldKey;

                $groups[$groupKey] ??= [
                    'model_type' => $term->model_type,
                    'model_id' => (int) $term->model_id,
                    'field_key' => $fieldKey,
                    'table' => $this->modelTable($term->model_type, (string) $term->key),
                    'terms' => [],
                    'meta' => [],
                ];
                $groups[$groupKey]['terms'][] = $term;

                continue;
            }

            $standaloneTerms[] = $term;
        }

        if (Schema::hasTable('revision_meta')) {
            foreach (DB::table('revision_meta')->get() as $meta) {
                $modelKey = $meta->model_type.'|'.$meta->model_id;
                $currentVersions[$modelKey] ??= $this->currentVersion($meta->model_type, (int) $meta->model_id);

                if ($meta->model_version !== null && (int) $meta->model_version !== $currentVersions[$modelKey]) {
                    continue;
                }

                $groupKey = $modelKey.'|'.$meta->meta_key;
                $groups[$groupKey] ??= [
                    'model_type' => $meta->model_type,
                    'model_id' => (int) $meta->model_id,
                    'field_key' => $meta->meta_key,
                    'table' => $this->modelTable($meta->model_type, ''),
                    'terms' => [],
                    'meta' => [],
                ];
                $groups[$groupKey]['meta'][] = $meta;
            }
        }

        $termMap = [];
        foreach ($terms as $term) {
            foreach ($definitionsByTermId->get($term->id, collect()) as $definition) {
                $termMap[$term->key][$definition->locale] = $this->decode($definition->content);
            }
        }

        $contentByGroup = [];
        $canonicalByGroup = [];

        foreach ($groups as $groupKey => $group) {
            $currentTerms = collect($group['terms'])
                ->filter(fn ($term) => $term->model_version === null
                    || (int) $term->model_version === $currentVersions[$group['model_type'].'|'.$group['model_id']])
                ->values();

            if ($currentTerms->isEmpty() && $group['terms'] !== []) {
                $maxVersion = collect($group['terms'])->max('model_version');
                $currentTerms = collect($group['terms'])
                    ->where('model_version', $maxVersion)
                    ->values();
            }

            $fieldKey = $group['field_key'];
            $canonical = $currentTerms->first(fn ($term) => $this->isFieldTerm((string) $term->key, $fieldKey))
                ?? $currentTerms->first();

            $canonicalByGroup[$groupKey] = $canonical;
            $values = [];
            if ($canonical) {
                foreach ($definitionsByTermId->get($canonical->id, collect()) as $definition) {
                    $values[$definition->locale] = $this->resolveLegacyReferences(
                        $this->decode($definition->content),
                        (string) $definition->locale,
                        $termMap
                    );
                }
            }

            foreach ($group['meta'] as $meta) {
                if (! array_key_exists('*', $values)) {
                    $values['*'] = $this->decode($meta->meta_value);
                }
            }

            if ($values !== []) {
                $contentByGroup[$groupKey] = $values;
            }
        }

        $standaloneContent = [];
        foreach ($standaloneTerms as $term) {
            $translations = [];
            foreach ($definitionsByTermId->get($term->id, collect()) as $definition) {
                $translations[$definition->locale] = $this->decode($definition->content);
            }
            if ($translations !== []) {
                $standaloneContent[$term->id] = $translations;
            }
        }

        DB::transaction(function () use ($terms, $groups, $contentByGroup, $canonicalByGroup, $standaloneContent, $standaloneTerms) {
            foreach ($terms as $term) {
                DB::table('i18n_terms')->where('id', $term->id)->update([
                    'key' => '__fabriq_content_migration_'.$term->id,
                ]);
            }
            DB::table('i18n_definitions')->delete();
            DB::table('i18n_terms')->delete();

            $normalizedByOldKey = [];

            foreach ($groups as $groupKey => $group) {
                $fieldKey = $group['field_key'];
                $newKey = $group['table'].'-'.$group['model_id'].'-'.$fieldKey;
                $canonical = $canonicalByGroup[$groupKey] ?? null;
                $data = [
                    'key' => $newKey,
                    'model_type' => $group['model_type'],
                    'model_id' => $group['model_id'],
                    'model_version' => null,
                    'field_key' => $fieldKey,
                    'description' => $canonical->description ?? $fieldKey,
                    'group' => $canonical->group ?? 'ungrouped',
                    'created_at' => $canonical->created_at ?? now(),
                    'updated_at' => now(),
                ];
                $termId = DB::table('i18n_terms')->insertGetId($data);

                if ($canonical) {
                    $normalizedByOldKey[$canonical->key] = $fieldKey;
                }

                $content = $contentByGroup[$groupKey] ?? [];
                if ($content !== []) {
                    DB::table('i18n_definitions')->insert([
                        'term_id' => $termId,
                        'locale' => '*',
                        'content' => json_encode($content, JSON_THROW_ON_ERROR),
                        'approved' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            foreach ($standaloneTerms as $term) {
                $termId = DB::table('i18n_terms')->insertGetId([
                    'key' => $term->key,
                    'model_type' => $term->model_type,
                    'model_id' => $term->model_id,
                    'model_version' => null,
                    'field_key' => $term->field_key,
                    'description' => $term->description,
                    'group' => $term->group,
                    'created_at' => $term->created_at,
                    'updated_at' => now(),
                ]);
                $content = $standaloneContent[$term->id] ?? [];
                if ($content !== []) {
                    DB::table('i18n_definitions')->insert([
                        'term_id' => $termId,
                        'locale' => '*',
                        'content' => json_encode($content, JSON_THROW_ON_ERROR),
                        'approved' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            if (Schema::hasTable('slugs')) {
                foreach ($normalizedByOldKey as $oldKey => $newKey) {
                    DB::table('slugs')->where('source_key', $oldKey)->update(['source_key' => $newKey]);
                }
            }
        });

        Schema::table('i18n_definitions', function (Blueprint $table) {
            $table->index('term_id', 'i18n_definitions_term_id_index');
            $table->dropUnique(['term_id', 'locale']);
            $table->dropIndex(['term_id', 'locale']);
            $table->dropColumn('locale');
        });

        Schema::table('i18n_terms', function (Blueprint $table) {
            $table->dropIndex('i18n_terms_lookup_idx');
            $table->dropColumn('model_version');
            $table->unique(['model_type', 'model_id', 'field_key'], 'i18n_terms_model_field_unique');
        });

        foreach (['revision_meta', 'revision_snapshots'] as $tableName) {
            Schema::dropIfExists($tableName);
        }

        foreach (['pages', 'articles'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $columns = array_filter(
                    ['revision', 'published_version', ...($tableName === 'pages' ? ['published_at'] : [])],
                    fn ($column) => Schema::hasColumn($tableName, $column)
                );
                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasTable('blocks') && Schema::hasColumn('blocks', 'revision')) {
            Schema::table('blocks', fn (Blueprint $table) => $table->dropColumn('revision'));
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Revision history and locale rows were consolidated and cannot be restored.');
    }

    private function inferFieldKey(string $termKey, array $fieldKeys): ?string
    {
        foreach ($fieldKeys as $fieldKey) {
            if ($this->isFieldTerm($termKey, (string) $fieldKey)) {
                return (string) $fieldKey;
            }
        }

        return null;
    }

    private function isFieldTerm(string $termKey, string $fieldKey): bool
    {
        return str_ends_with($termKey, '-'.$fieldKey)
            || str_ends_with($termKey, '_'.$fieldKey)
            || str_contains($termKey, '-'.$fieldKey.'--')
            || str_contains($termKey, '_'.$fieldKey.'__');
    }

    private function currentVersion(string $modelType, int $modelId): int
    {
        $table = $this->modelTable($modelType, '');

        return $table !== '' && Schema::hasTable($table) && Schema::hasColumn($table, 'revision')
            ? (int) (DB::table($table)->where('id', $modelId)->value('revision') ?? 0)
            : 0;
    }

    private function modelTable(string $modelType, string $termKey): string
    {
        $class = Relation::getMorphedModel($modelType) ?? (class_exists($modelType) ? $modelType : null);
        if ($class && class_exists($class)) {
            return (new $class)->getTable();
        }

        $table = match ($modelType) {
            'fabriq_page', 'Karabin\\Fabriq\\Models\\Page' => 'pages',
            'fabriq_article', 'Karabin\\Fabriq\\Models\\Article' => 'articles',
            'fabriq_contact', 'Karabin\\Fabriq\\Models\\Contact' => 'contacts',
            'fabriq_event', 'Karabin\\Fabriq\\Models\\Event' => 'events',
            'fabriq_menuItem', 'Karabin\\Fabriq\\Models\\MenuItem' => 'menu_items',
            'fabriq_smartBlock', 'Karabin\\Fabriq\\Models\\SmartBlock' => 'smart_blocks',
            default => '',
        };

        if ($table !== '') {
            return $table;
        }

        return $termKey !== '' ? explode('-', $termKey, 2)[0] : '';
    }

    private function decode(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }

    private function resolveLegacyReferences(mixed $value, string $locale, array $termMap): mixed
    {
        if (is_string($value) && isset($termMap[$value][$locale])) {
            return $this->resolveLegacyReferences($termMap[$value][$locale], $locale, $termMap);
        }

        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->resolveLegacyReferences($item, $locale, $termMap);
            }
        }

        return $value;
    }
};
