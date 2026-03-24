<?php

namespace Karabin\Fabriq\Http\Controllers\Admin\Concerns;

use Karabin\Fabriq\Models\BlockType;
use Karabin\TranslatableRevisions\Models\RevisionTemplate;

trait TransformsBlockTypes
{
    /**
     * @param  iterable<int, BlockType>  $blockTypes
     * @return array<int, array<string, mixed>>
     */
    protected function transformBlockTypes(iterable $blockTypes): array
    {
        $items = [];

        foreach ($blockTypes as $blockType) {
            $options = $this->normalizeBlockTypeOptions($blockType->options);

            $items[] = [
                'id' => $blockType->id,
                'name' => $blockType->name,
                'componentName' => $blockType->component_name,
                'base64Svg' => $blockType->base_64_svg,
                'hasChildren' => (bool) $blockType->has_children,
                'options' => $options,
                'createdAt' => $blockType->created_at?->toIso8601String(),
                'updatedAt' => $blockType->updated_at?->toIso8601String(),
                'editPath' => '/admin/block-types/'.$blockType->id.'/edit',
            ];
        }

        return $items;
    }

    /**
     * @param  mixed  $value
     * @return array{recommendedFor: array<int, string>, visibleFor: array<int, string>, hiddenFor: array<int, string>}
     */
    protected function normalizeBlockTypeOptions($value): array
    {
        $options = is_array($value) ? $value : [];

        return [
            'recommendedFor' => array_values(array_filter((array) data_get($options, 'recommended_for', []), fn ($item) => is_string($item) && $item !== '')),
            'visibleFor' => array_values(array_filter((array) data_get($options, 'visible_for', []), fn ($item) => is_string($item) && $item !== '')),
            'hiddenFor' => array_values(array_filter((array) data_get($options, 'hidden_for', []), fn ($item) => is_string($item) && $item !== '')),
        ];
    }

    /**
     * @return array<int, array{id: int, name: string, slug: string, type: string}>
     */
    protected function templateOptions(): array
    {
        return RevisionTemplate::query()
            ->orderBy('name')
            ->get()
            ->map(fn (RevisionTemplate $template): array => [
                'id' => (int) $template->id,
                'name' => (string) $template->name,
                'slug' => (string) $template->slug,
                'type' => (string) $template->type,
            ])
            ->values()
            ->all();
    }
}
