<?php

namespace Karabin\Fabriq\Services;

use Karabin\Fabriq\ContentGetters\ContentResourceContext;

class ContentReferenceExtractor
{
    public function extract(mixed $value, ?string $key = null): array
    {
        $references = [];
        $this->collect($value, $references, $key);

        return array_values($references);
    }

    private function collect(mixed $value, array &$references, ?string $key = null): void
    {
        if ($value instanceof \stdClass) {
            $value = (array) $value;
        }

        $registry = app(ReferenceRegistry::class);
        if ($key !== null && $registry->hasField($key)) {
            $field = $registry->extract($key, $value);
            $this->addExtractedIds($field['type'], $field['ids'], $references);

            return;
        }

        if ($key === 'button') {
            $this->collectButton($value, $references);

            return;
        }

        if ($key === 'buttons' && is_array($value)) {
            foreach ($value as $button) {
                $this->collectButton($button, $references);
            }

            return;
        }

        if (is_array($value)) {
            foreach ($value as $childKey => $child) {
                $this->collect($child, $references, is_string($childKey) ? $childKey : null);
            }
        }
    }

    private function collectButton(mixed $value, array &$references): void
    {
        $value = $value instanceof \stdClass ? (array) $value : $value;
        if (! is_array($value)) {
            return;
        }

        $type = $value['linkType'] ?? null;
        if (is_string($type) && str_contains($type, 'internal')) {
            $this->addIds('page', $value['page_id'] ?? null, $references);
        } elseif ($type === 'file') {
            $this->addIds('file', $value['file'] ?? null, $references);
        }
    }

    private function addIds(string $type, mixed $value, array &$references): void
    {
        $this->addExtractedIds($type, ContentResourceContext::extractIds($value), $references);
    }

    private function addExtractedIds(string $type, iterable $ids, array &$references): void
    {
        foreach ($ids as $id) {
            if ((! is_int($id) && ! ctype_digit($id)) || (int) $id <= 0) {
                continue;
            }

            $id = (int) $id;
            $references[$type.':'.$id] = [
                'resource_type' => $type,
                'resource_id' => $id,
            ];
        }
    }
}
