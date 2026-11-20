<?php

namespace Karabin\Fabriq\Services;

use Illuminate\Support\Facades\DB;
use Karabin\Fabriq\Models\I18nDefinition;

class DefinitionReferenceIndex
{
    public function sync(I18nDefinition $definition): void
    {
        $references = [];
        $owner = $definition->model_type && $definition->model_id ? $definition->model : null;
        $field = null;
        if ($owner && method_exists($owner, 'findContentTemplateField') && $definition->field_key) {
            $field = $owner->findContentTemplateField((string) $definition->field_key);
        }
        $key = $definition->field_key;
        if ($field !== null) {
            if ($field->repeater) {
                $key = null;
            } elseif (! app(ReferenceRegistry::class)->hasField((string) $key) && ! in_array($key, ['button', 'buttons'], true)) {
                $key = $field->type;
            }
        }
        $extractor = app(ContentReferenceExtractor::class);

        foreach ($definition->getTranslations('content') as $value) {
            foreach ($extractor->extract($value, $key) as $reference) {
                $references[$reference['resource_type'].':'.$reference['resource_id']] = [
                    'definition_id' => $definition->getKey(),
                    ...$reference,
                ];
            }
        }

        DB::table('content_references')->where('definition_id', $definition->getKey())->delete();
        if ($references !== []) {
            DB::table('content_references')->insert(array_values($references));
        }
    }
}
